import { ref, computed, type Ref } from "vue";
import { fetchSolution } from "@/composables/solveApi"

export type CarOrientation = "horizontal" | "vertical";
export type MoveDirection = "up" | "right" | "down" | "left";

export interface Car {
    id: number;
    x: number;
    y: number;
    originalX: number;
    originalY: number;
    length: number;
    orientation: CarOrientation;
    color: string;
}

export interface Move {
    raw: string;
    car: number;
    color: string;
    length: number;
    direction: MoveDirection;
    currentMove: boolean;
}

export interface Unsolvable {
    reason: string;
}

export function useRushHour() {
    /* Board basics */
    const gridSizeX = ref<number>(6);
    const gridSizeY = ref<number>(6);
    const cellSize = 100;
    const boardSizeX = computed(() => gridSizeX.value * cellSize);
    const boardSizeY = computed(() => gridSizeY.value * cellSize);
    const viewBox = computed(() => `0 0 ${boardSizeX.value} ${boardSizeY.value}`);

    const editMode = ref<boolean>(true);

    /* State */
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

    /* Drag state */
    const dragging = ref<boolean>(false);
    const dragCar: Ref<Car | null> = ref(null);
    const dragStart = ref({ x: 0, y: 0 });
    const carStart = ref({ x: 0, y: 0 });

    /* Grid */
    const cells = computed(() => {
        const result: { id: number; x: number; y: number }[] = [];
        let id = 0;
        for (let y = 0; y < gridSizeY.value; y++) {
            for (let x = 0; x < gridSizeX.value; x++) {
                result.push({
                    id: id++,
                    x: x * cellSize,
                    y: y * cellSize,
                });
            }
        }
        return result;
    });

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

    /* Add car */
    function addCar() {
        const newCar: Car = {
            id: idCounter++,
            x: 0,
            y: 0,
            originalX: 0,
            originalY: 0,
            length: 2,
            orientation: "horizontal",
            color: randomColor(idCounter),
        };
        cars.value.set(newCar.id, newCar);
        selectCar(newCar);
    }

    function getStroke(car: Car): string {
        if (targetCar.value && targetCar.value.id === car.id) {
            return selectedCar.value?.id === car.id ? "red" : "darkred";
        }
        return selectedCar.value?.id === car.id ? "black" : "none";
    }

    /* Select */
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

    function startDrag(event: MouseEvent, car: Car) {
        if (solvingPuzzle.value) return;
        dragging.value = true;
        dragCar.value = car;
        selectedCar.value = car;

        dragStart.value = {
            x: event.clientX,
            y: event.clientY,
        };

        carStart.value = {
            x: car.x,
            y: car.y,
        };

        window.addEventListener("mousemove", onDrag);
        window.addEventListener("mouseup", stopDrag);
    }

    function onDrag(event: MouseEvent) {
        if (!dragging.value || !dragCar.value) return;

        const dx = event.clientX - dragStart.value.x;
        const dy = event.clientY - dragStart.value.y;

        const car = dragCar.value;

        let newX = carStart.value.x;
        let newY = carStart.value.y;

        if (editMode.value) {
            newX = Math.round((carStart.value.x * cellSize + dx) / cellSize);
            newY = Math.round((carStart.value.y * cellSize + dy) / cellSize);

            const maxX = gridSizeX.value - (car.orientation === "horizontal" ? car.length : 1);
            const maxY = gridSizeY.value - (car.orientation === "vertical" ? car.length : 1);

            newX = Math.max(0, Math.min(maxX, newX));
            newY = Math.max(0, Math.min(maxY, newY));

            car.x = newX;
            car.y = newY;
        } else {
            if (car.orientation === "horizontal") {
                const delta = Math.round(dx / cellSize);
                newX = carStart.value.x + delta;

                newX = Math.max(0, Math.min(gridSizeX.value - car.length, newX));

                if (!collides(car, newX, car.y)) {
                    car.x = newX;
                }
            } else {
                const delta = Math.round(dy / cellSize);
                newY = carStart.value.y + delta;

                newY = Math.max(0, Math.min(gridSizeY.value - car.length, newY));

                if (!collides(car, car.x, newY)) {
                    car.y = newY;
                }
            }
        }
    }

    function stopDrag() {
        const lastDragCar = dragCar.value;
        if (lastDragCar) {
            if (carStart.value.x !== lastDragCar.x || carStart.value.y !== lastDragCar.y) {
                invalidateSolution();
            }
        }
        dragging.value = false;
        dragCar.value = null;
        if (targetCar.value && selectedCar.value && targetCar.value.id === selectedCar.value.id) {
            checkWin();
        }

        window.removeEventListener("mousemove", onDrag);
        window.removeEventListener("mouseup", stopDrag);
    }

    function collides(car: Car, newX: number, newY: number): boolean {
        for (const other of cars.value.values()) {
            if (other.id === car.id) continue;

            const carCells = getCells(car, newX, newY);
            const otherCells = getCells(other, other.x, other.y);

            for (const c1 of carCells) {
                for (const c2 of otherCells) {
                    if (c1.x === c2.x && c1.y === c2.y) return true;
                }
            }
        }
        return false;
    }

    function getCells(car: Car, x: number, y: number) {
        const result: { x: number; y: number }[] = [];
        for (let i = 0; i < car.length; i++) {
            if (car.orientation === "horizontal") {
                result.push({ x: x + i, y });
            } else {
                result.push({ x, y: y + i });
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
        if (car.orientation === "horizontal") {
            hasWon.value = car.x === 0;
        } else {
            hasWon.value = car.y === 0;
        }
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
        solution.value = [];
        unsolvable.value = false;
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

    function solvePuzzle() {
        if (retrievingSolution.value) return;

        retrievingSolution.value = true;
        solvingPuzzle.value = true;
        unsolvable.value = false;
        solution.value = [];
        solveAbortController = new AbortController()
        fetchSolution(
            gridSizeX.value,
            gridSizeY.value,
            cars.value,
            targetCar.value,
            solveAbortController.signal
        ).then((result) => {
            retrievingSolution.value = false;
            if (typeof result === 'object' && Array.isArray(result)) {
                solution.value = result;
            } else {
                console.error("Result is not an array");
            }
        },
            (reason) => {
                retrievingSolution.value = false;
                solvingPuzzle.value = false;
                if (reason.reason) {
                    unsolvable.value = { reason: reason.reason };
                }
            }
        );
    }

    function playSolve() {
        if (playingSolution.value) {
            return;
        }
        playingSolution.value = true;
        const currentStepIndex = solution.value.findIndex((move) => move.currentMove) ?? 0;
        playSolveSteps(currentStepIndex >= 0 ? currentStepIndex : 0);
    }
    function playSolveSteps(currentStepIndex: number) {
        if (playingSolution.value === false) {
            // We are paused or something.
            return;
        }
        if (solution.value.length <= currentStepIndex) {
            console.error("solution index out of bounds");
            return;
        }
        const currentMove = solution.value[currentStepIndex];
        const movingCar = cars.value.get(currentMove.car);
        if (!movingCar) return;
        selectCar(movingCar);
        moveSelectedCar(currentMove.length, currentMove.direction);
        setTimeout(() => {
            currentMove.currentMove = false;
            const nextMove = solution.value[currentStepIndex + 1];
            if (nextMove) {
                nextMove.currentMove = true;
                playSolveSteps(currentStepIndex + 1);
            } else {
                stopSolve();
                checkWin();
            }
        }, 300);
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
    function invalidateSolution() {
        solvingPuzzle.value = false;
    }

    function randomColor(id: number): string {
        const angle = (id * 42.5) % 360;
        return `hsl(${angle}, 70%, 60%)`;
    }

    return {
        cellSize,
        boardSizeX,
        boardSizeY,
        viewBox,
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
        toggleEditMode,
        addCar,
        getStroke,
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
        pauseSolve,
        stopSolve,
    }
}