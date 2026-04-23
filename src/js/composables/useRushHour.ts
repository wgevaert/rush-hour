import { ref, computed, type Ref } from "vue";
import { fetchSolution } from "@/composables/solveApi"
import { fetchBoard, postSaveBoard } from "@/composables/boardApi"
import { getUrlParam } from "@/composables/httpApi";

export type CarOrientation = "horizontal" | "vertical";
export type MoveDirection = "up" | "right" | "down" | "left";

export interface BaseCar {
    id: number;
    x: number;
    y: number;
    length: number;
    orientation: CarOrientation;
}

export interface Car extends BaseCar {
    originalX: number;
    originalY: number;
    color: string;
}

export function getCarLengthX(car: BaseCar): number {
    return car.orientation === 'horizontal' ? car.length : 1;
}

export function getCarLengthY(car: BaseCar): number {
    return car.orientation === 'vertical' ? car.length : 1;
}

export interface Move {
    raw: string;
    car: number;
    length: number;
    direction: MoveDirection;
    currentMove: boolean;
}

export interface Unsolvable {
    reason: string;
}

export function instanceOfUnsolvable(something: any): something is Unsolvable {
    return 'reason' in something;
}

export interface BoardExchange {
    gridSizeX: number,
    gridSizeY: number,
    cars: Map<number, BaseCar>,
    targetCar: BaseCar,
}

export function useRushHour() {
    /* Board basics */
    const gridSizeX = ref<number>(6);
    const gridSizeY = ref<number>(6);
    const cellSize = ref<number>(100);
    const boardSizeX = computed(() => gridSizeX.value * cellSize.value);
    const boardSizeY = computed(() => gridSizeY.value * cellSize.value);

    const editMode = ref<boolean>(true);
    const saveLoadMessage = ref<string>("");

    const cars: Ref<Map<number, Car>> = ref(new Map());
    const selectedCar: Ref<Car | null> = ref(null);
    const targetCar: Ref<Car | null> = ref(null);

    const hasWon = ref<boolean>(false);
    const solution = ref<Move[]>([]);
    const unsolvable = ref<Unsolvable | false>(false);
    const retrievingSolution = ref<boolean>(false);
    const solvingPuzzle = ref<boolean>(false);
    const playingSolution = ref<boolean>(false);

    let solveAbortController: AbortController | null = null;
    let idCounter = 1;

    const dragStart = ref({ x: 0, y: 0 });
    const carDragStart: Ref<BaseCar | null> = ref(null);
    const saveLoadInput: Ref<string> = ref("");

    const cells = computed(() => {
        const result: { id: number; x: number; y: number }[] = [];
        let id = 0;
        for (let y = 0; y < gridSizeY.value; y++) {
            for (let x = 0; x < gridSizeX.value; x++) {
                result.push({
                    id: id++,
                    x: x * cellSize.value,
                    y: y * cellSize.value,
                });
            }
        }
        return result;
    });

    function sleep(ms: number): Promise<void> {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    function toggleEditMode() {
        if (!editMode.value) {
            resetBoard();
        }
        editMode.value = !editMode.value;
        for (const car of cars.value.values()) {
            car.originalX = car.x;
            car.originalY = car.y;
        }
        unsolvable.value = false;
    }

    function addCar() {
        const newCar: Car = makeCar({
            id: idCounter++,
            x: 0,
            y: 0,
            length: 2,
            orientation: "horizontal",
        });
        cars.value.set(newCar.id, newCar);
        selectCar(newCar);
    }

    function selectCar(car: Car | null) {
        selectedCar.value = car;
    }

    function removeSelectedCar() {
        if (!selectedCar.value) return;
        cars.value.delete(selectedCar.value.id);
        if (targetCar.value?.id === selectedCar.value.id) {
            targetCar.value = null;
        }
        selectedCar.value = null;
    }

    function rotateSelectedCar() {
        if (!selectedCar.value) return;
        selectedCar.value.orientation = selectedCar.value.orientation === "horizontal" ? "vertical" : "horizontal";
    }

    function lengthenSelectedCar() {
        const car = selectedCar.value;
        if (!car) return;
        if (
            (car.orientation === "horizontal" && car.length + car.x < gridSizeX.value) ||
            (car.orientation === "vertical" && car.length + car.y < gridSizeY.value)
        ) {
            car.length++;
        }
    }

    function shortenSelectedCar() {
        const car = selectedCar.value;
        if (!car) return;
        if (car.length > 1) car.length--;
    }

    function makeSelectedTarget() {
        if (!selectedCar.value) return;
        targetCar.value = selectedCar.value;
    }

    function getExitX() : number {
        return targetCar.value?.orientation === 'horizontal' ? 0 : targetCar.value?.x || 0;
    }

    function getExitY() : number {
        return targetCar.value?.orientation === 'vertical' ? 0 : targetCar.value?.y || 0;
    }

    function startDrag(event: MouseEvent, car: Car) {
        // Allowing drag when a solution has been calculated would invalidate the solution
        // So we don't allow dragging in that case.
        if (solvingPuzzle.value) return;

        selectCar(car);

        dragStart.value = {
            x: event.clientX,
            y: event.clientY,
        };

        carDragStart.value = {
            id: car.id,
            x: car.x,
            y: car.y,
            orientation: car.orientation,
            length: car.length
        };

        window.addEventListener("mousemove", onDrag);
        window.addEventListener("mouseup", stopDrag);
    }

    function onDrag(event: MouseEvent) {
        if (!dragStart.value || !carDragStart.value) return;

        const dx = Math.round((event.clientX - dragStart.value.x) / cellSize.value);
        const dy = Math.round((event.clientY - dragStart.value.y) / cellSize.value);

        moveSelectedCarFromStartByDisplacement(carDragStart.value, dx, dy);
    }

    function stopDrag() {
        carDragStart.value = null;
        if (targetCar.value && selectedCar.value && targetCar.value.id === selectedCar.value.id) {
            checkWin();
        }

        window.removeEventListener("mousemove", onDrag);
        window.removeEventListener("mouseup", stopDrag);
    }

    function moveSelectedCarFromStartByDisplacement(carStart: BaseCar, dx: number, dy: number) {
        if (!selectedCar.value) return;

        const maxX = gridSizeX.value - getCarLengthX(carStart);
        const maxY = gridSizeY.value - getCarLengthY(carStart);

        let newX = Math.max(0, Math.min(maxX, carStart.x + dx));
        let newY = Math.max(0, Math.min(maxY, carStart.y + dy));

        if (editMode.value) {
            // No collision checks in edit mode
            selectedCar.value.x = newX;
            selectedCar.value.y = newY;
        } else {
            // In play mode, only move the car in the allowed direction
            if (carStart.orientation === "horizontal") {
                newY = carStart.y;
            } else {
                newX = carStart.x;
            }
            if (!collides(carStart, newX, newY)) {
                selectedCar.value.x = newX;
                selectedCar.value.y = carStart.y;
            }
        }
    }

    function collides(car: BaseCar, newX: number, newY: number): boolean {
        const carTrajectoryCells = getTrajectoryCells(car, newX, newY);
        for (const other of cars.value.values()) {
            if (other.id === car.id) continue;

            const otherCells = getCells(other);

            for (const c1 of carTrajectoryCells) {
                for (const c2 of otherCells) {
                    if (c1.x === c2.x && c1.y === c2.y) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    function getCells(car: Car) {
        const result: { x: number; y: number }[] = [];
        for (let x = 0; x < getCarLengthX(car); x++) {
            for (let y = 0; y < getCarLengthY(car); y++) {
                result.push({ x, y });
            }
        }
        return result;
    }

    function getTrajectoryCells(car: BaseCar, newX: number, newY: number) {
        const result: { x: number; y: number }[] = [];
        const startX: number = Math.min(newX, car.x),
            endX: number = Math.max(newX, car.x) + getCarLengthX(car),
            startY: number = Math.min(newY, car.y),
            endY: number = Math.max(newY, car.y) + getCarLengthY(car);

        for (let x = startX; x <= endX; x++) {
            for (let y = startY; y <= endY; y++) {
                result.push({ x, y })
            }
        }
        return result;
    }

    function checkWin() {
        if (!targetCar.value) {
            hasWon.value = false;
            return;
        }
        const car = targetCar.value;
        hasWon.value = car.x === getExitX() && car.y === getExitY();
    }

    function dismissWin() {
        hasWon.value = false;
    }

    function resetBoard() {
        stopSolve();
        for (const car of cars.value.values()) {
            car.x = car.originalX;
            car.y = car.originalY;
        }
        hasWon.value = false;
    }

    function clearBoard() {
        if (!cars.value.size || window.confirm("Are you sure that you want to clear all cars from the board?")) {
            cars.value = new Map();
            selectedCar.value = null;
            targetCar.value = null;
            idCounter = 1;
            resetBoard();
        }
    }

    async function saveBoard() {
        if (!targetCar.value) {
            return;
        }
        const board: BoardExchange = {
            gridSizeX: gridSizeX.value,
            gridSizeY: gridSizeY.value,
            cars: cars.value,
            targetCar: targetCar.value,
        };
        try {
            const saveResult = await postSaveBoard(board);
            if (saveResult !== null) {
                saveLoadInput.value = saveResult || "";
            }
            saveLoadMessage.value = "Saved under id " + saveResult;
        } catch (failReason) {
            saveLoadMessage.value = "failure";
        }
    }

    async function loadBoard() {
        const retrievedBoard: BoardExchange | null = await fetchBoard(saveLoadInput.value);
        if (retrievedBoard === null) {
            saveLoadMessage.value = 'Could not load board';
            return;
        }
        clearBoard();
        initBoard(retrievedBoard);
    }

    async function loadBoardFromUrl() {
        const board = getUrlParam('board');
        if (board === null) {
            return;
        }
        saveLoadInput.value = board;
        await loadBoard();
    }

    function initBoard(board: BoardExchange): void {
        gridSizeX.value = board.gridSizeX;
        gridSizeY.value = board.gridSizeY;

        for (const car of board.cars.values()) {
            cars.value.set(car.id, makeCar(car));
            idCounter = Math.max(idCounter, car.id+1);
        }
        targetCar.value = cars.value.get(board.targetCar.id) || null;
    }

    function makeCar(car: BaseCar): Car {
        return {
            ...car,
            originalX: car.x,
            originalY: car.y,
            color: getColor(car.id),
        };
    }

    async function solvePuzzle() {
        if (retrievingSolution.value) return;

        retrievingSolution.value = true;
        solvingPuzzle.value = true;
        unsolvable.value = false;
        solution.value = [];
        solveAbortController = new AbortController()
        try {
            if (!targetCar.value) {
                throw { reason: "Cannot solve without objective" };
            }
            const boardExchange: BoardExchange = {
                gridSizeX: gridSizeX.value,
                gridSizeY: gridSizeY.value,
                cars: cars.value,
                targetCar: targetCar.value,
            };
            const result = await fetchSolution(
                boardExchange,
                solveAbortController.signal
            )
            retrievingSolution.value = false;
            if (typeof result === 'object' && Array.isArray(result)) {
                solution.value = result;
            } else {
                console.error("Result is not an array");
            }
        } catch (reason: any) {
            retrievingSolution.value = false;
            solvingPuzzle.value = false;
            if (instanceOfUnsolvable(reason)) {
                unsolvable.value = { reason: reason.reason };
            } else {
                unsolvable.value = { reason: 'An unknown error occurred' }
                console.error("Solve failed with reason %O", reason);
            }
        }
    }

    function playSolve() {
        if (playingSolution.value) {
            // If already playing, don't play twice.
            return;
        }
        playingSolution.value = true;

        playSolveSteps();
    }

    function playSolveStep(): boolean {
        const currentStepIndex: number = solution.value.findIndex((move) => move.currentMove) ?? -1;
        const currentMove = solution.value[currentStepIndex];

        if (!currentMove) {
            return false;
        }
        const movingCar = cars.value.get(currentMove.car);
        if (!movingCar) {
            console.error("Move references unknown car");
            return false;
        }

        selectCar(movingCar);
        moveSelectedCar(currentMove.length, currentMove.direction);
        currentMove.currentMove = false;
        if (solution.value.length > currentStepIndex + 1) {
            solution.value[currentStepIndex + 1].currentMove = true;

            return true;
        }
        stopSolve();
        checkWin();
        return false;
    }

    async function playSolveSteps() {
        while (playSolveStep()) {
            await sleep(300);
            if (playingSolution.value === false) {
                // We are paused or something.
                return;
            }
        }
    }
    function playSingleSolveStep() {
        pauseSolve();
        playSolveStep();
    }

    function pauseSolve() {
        playingSolution.value = false;
    }

    function moveSelectedCar(length: number, direction: MoveDirection) {
        switch (direction) {
            case "up":
                return moveSelectedCarUp(length);
            case "right":
                return moveSelectedCarRight(length);
            case "down":
                return moveSelectedCarDown(length);
            case "left":
                return moveSelectedCarLeft(length);
        }
    }
    function moveSelectedCarUp(length: number) {
        if (!selectedCar.value) return;
        selectedCar.value.y -= length;
    }
    function moveSelectedCarDown(length: number) {
        if (!selectedCar.value) return;
        selectedCar.value.y += length;
    }
    function moveSelectedCarLeft(length: number) {
        if (!selectedCar.value) return;
        selectedCar.value.x -= length;
    }
    function moveSelectedCarRight(length: number) {
        if (!selectedCar.value) return;
        selectedCar.value.x += length;
    }

    function stopSolve() {
        if (solveAbortController) {
            solveAbortController.abort();
            solveAbortController = null;
        }
        retrievingSolution.value = false;
        solvingPuzzle.value = false;
        playingSolution.value = false;
        unsolvable.value = false;
        solution.value = [];
    }

    function getColor(id: number): string {
        const angle = (id * 42.5) % 360;
        return `hsl(${angle}, 70%, 60%)`;
    }

    return {
        cellSize,
        boardSizeX,
        boardSizeY,
        editMode,
        hasWon,
        solution,
        unsolvable,
        retrievingSolution,
        solvingPuzzle,
        playingSolution,
        cells,
        selectedCar,
        cars,
        targetCar,
        saveLoadInput,
        saveBoard,
        loadBoard,
        getExitX,
        getExitY,
        loadBoardFromUrl,
        toggleEditMode,
        addCar,
        selectCar,
        removeSelectedCar,
        rotateSelectedCar,
        lengthenSelectedCar,
        shortenSelectedCar,
        makeSelectedTarget,
        startDrag,
        dismissWin,
        resetBoard,
        clearBoard,
        solvePuzzle,
        playSolve,
        playSingleSolveStep,
        pauseSolve,
        stopSolve,
    }
}