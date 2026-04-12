<template>
  <div class="app">
    <aside class="controls">
      <h2>Rush Hour Controls</h2>

      <button :disabled="solvingPuzzle" @click="toggleEditMode()">
        {{ editMode ? "Play puzzle" : "Edit puzzle" }}
      </button>
      <template v-if="editMode">
        <h3>Edit puzzle</h3>
        <button @click="addCar">Add Car</button>
        <button @click="clearBoard">Clear Board</button>
        <button :disabled="!selectedCar" @click="rotateSelectedCar">Rotate Car</button>
        <button :disabled="!selectedCar" @click="lengthenSelectedCar">Lengthen Car</button>
        <button :disabled="!selectedCar" @click="shortenSelectedCar">Shorten Car</button>
        <button :disabled="!selectedCar" @click="removeSelectedCar">Delete Car</button>
        <button :disabled="!selectedCar" @click="makeSelectedTarget">Make Car Objective</button>
      </template>
      <template v-if="!editMode">
        <h3>Play puzzle</h3>
        <button @click="resetBoard">Reset</button>
        <button @click="solvePuzzle">Solve</button>
        <article class="solution-box">
          <section class="solution-info">
            <p v-if="retrievingSolution">Retrieving Solution</p>
            <p v-if="unsolvable">Could not solve puzzle. <template v-if="unsolvable.reason">Reason: {{unsolvable.reason}}</template></p>
            <p v-if="solution"><template v-for="move in solution"><span class="solve-step" :class="move.currentMove ? 'active-solve-step' : ''"><span :style="{'color': move.color}">{{move.car}}</span>{{move.direction}}{{move.length}}</span></template></p>
          </section>
          <section v-if="solvingPuzzle" class="solution-buttons">
            <button :disabled="!solution.length || playingSolution" @click="playSolve">Play</button>
            <button :disabled="!playingSolution" @click="pauseSolve">Pause</button>
            <button @click="stopSolve">Stop</button>
          </section>
        </article>
      </template>
    </aside>

    <transition name="fade">
      <article v-if="hasWon && !editMode" class="win-overlay">
        <div class="win-box">
          <button class="cancel" @click="dismissWin">X</button>
          <h1>You Win!</h1>
          <button @click="resetBoard">
            Play Again
          </button>
        </div>
      </article>
    </transition>

    <svg
      class="board"
      :width="boardSizeX"
      :height="boardSizeY"
      :viewBox="viewBox"
    >
      <!-- Grid -->
      <g>
        <rect
          v-for="cell in cells"
          :key="cell.id"
          :x="cell.x"
          :y="cell.y"
          width="100"
          height="100"
          fill="none"
          stroke="#ccc"
        />
      </g>

      <!-- Cars -->
      <g>
        <rect
          v-for="car in Array.from(cars.values())"
          :key="car.id"
          :x="car.x * cellSize"
          :y="car.y * cellSize"
          :width="car.orientation === 'horizontal' ? car.length * cellSize : cellSize"
          :height="car.orientation === 'vertical' ? car.length * cellSize : cellSize"
          :fill="car.color"
          rx="10"
          @mousedown="startDrag($event, car)"
          @click="selectCar(car)"
          :stroke="getStroke(car)"
          stroke-width="4"
        />
      </g>

      <!-- EXIT -->
      <g id="exit">
        <rect
          v-if="targetCar"
          :x="targetCar.orientation === 'vertical' ? targetCar.x * cellSize : 0"
          :y="targetCar.orientation === 'horizontal' ? targetCar.y * cellSize : 0"
          width="100"
          height="100"
          fill="gold"
          opacity="0.5"
          stroke="orange"
          stroke-width="4"
        />
      </g>
    </svg>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, type Ref } from "vue";

type CarOrientation = "horizontal" | "vertical";
type MoveDirection = "up" | "right" | "down" | "left";

interface Car {
  id: number;
  x: number;
  y: number;
  originalX: number;
  originalY: number;
  length: number;
  orientation: CarOrientation;
  color: string;
}

interface Move {
  raw: string;
  car: number;
  color: string;
  length: number;
  direction: MoveDirection;
  currentMove: boolean;
}

interface Unsolvable {
  reason: string;
}

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
let carNameCounter = 0;
const carNames: Map<string, number> = new Map();

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

/* DRAG START */
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

/* DRAG MOVE */
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

/* DRAG END */
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

/* Collision detection */
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

  const serializedBoard = serializeBoard();
  if (!serializedBoard) return;

  retrievingSolution.value = true;
  solvingPuzzle.value = true;
  unsolvable.value = false;
  solution.value = [];
  solveAbortController = new AbortController();
  fetch("http://localhost:8000/api.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: new URLSearchParams({ action: "solve", board: serializedBoard }),
    signal: solveAbortController.signal,
  })
    .then((response) => {
      retrievingSolution.value = false;
      response.json().then(
        (parsedResponse) => handleSolveResponse(parsedResponse),
        (failReason) => {
          solvingPuzzle.value = false;
          unsolvable.value = { reason: "Endpoint json failure" };
          console.error("Solve endpoint response could not be interpreted as json with reason %O", failReason);
        }
      );
    })
    .catch((failReason) => {
      retrievingSolution.value = false;
      solvingPuzzle.value = false;
      if (failReason && failReason.name === "AbortError") {
        unsolvable.value = { reason: "aborted" };
      } else {
        unsolvable.value = { reason: "Endpoint failure" };
        console.error("Solve endpoint failed with reason %O", failReason);
      }
    });
}

function serializeBoard(): string | false {
  let serializedBoard = `${gridSizeX.value},${gridSizeY.value}$`;
  if (!targetCar.value) {
    unsolvable.value = { reason: "Cannot solve without objective" };
    return false;
  }
  if (targetCar.value.orientation === "horizontal") {
    serializedBoard = serializedBoard + "0," + (targetCar.value.y + 1);
  } else {
    serializedBoard = serializedBoard + (targetCar.value.x + 1) + ",0";
  }
  serializedBoard = serializedBoard + ";";
  const serializedCars: string[] = [];

  carNames.clear();
  carNameCounter = 0;
  for (const car of cars.value.values()) {
    serializedCars.push(getCarName(car) + (car.x + 1) + "," + (car.y + 1) + (car.orientation === "horizontal" ? "R" : "D") + car.length);
  }
  serializedBoard = serializedBoard + serializedCars.join("|");
  return serializedBoard;
}

function getCarName(car: Car): string {
  const alphabet = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
  if (targetCar.value && targetCar.value.id === car.id) {
    // The target car must be called 'r';
    carNames.set("r", car.id);
    return "r";
  }
  if (carNameCounter < alphabet.length && alphabet.charAt(carNameCounter) === "r") {
    // A car that is not the target car must not be called 'r';
    carNameCounter++;
  }
  let value = carNameCounter++;
  let name = "";
  do {
    const remainder = value % alphabet.length;
    name = name + alphabet.charAt(remainder);
    value = (value - remainder) / alphabet.length;
  } while (value != 0);

  carNames.set(name, car.id);
  return name;
}

function handleSolveResponse(response: any) {
  if (!response.solved) {
    const reason = response.reason || (response.error ? "An error occurred" : "Puzzle is not solvable");
    unsolvable.value = { reason };
    solvingPuzzle.value = false;
    return;
  }
  if (response.moves) {
    for (const move of response.moves) {
      const parsedMove = parseMove(move);
      if (parsedMove) {
        solution.value.push(parsedMove);
      }
    }
  }
  const firstMove = solution.value[0];
  if (firstMove) {
    firstMove.currentMove = true;
  }
}

function parseMove(move: any): Move | null {
  if (typeof move !== "string" && !(move instanceof String)) {
    return null;
  }
  const moveRegexp = /^([a-zA-Z]+)([0-9]+)([NESW])$/;
  const moveParts = (move as string).match(moveRegexp);
  if (!moveParts || !moveParts[1] || !moveParts[2] || !moveParts[3]) {
    return null;
  }
  if (!carNames.has(moveParts[1])) {
    return null;
  }
  const carId = carNames.get(moveParts[1])!;
  if (!cars.value.has(carId)) {
    return null;
  }
  const moveLength = parseInt(moveParts[2], 10);
  if (isNaN(moveLength)) {
    return null;
  }
  const directionMap: Record<string, MoveDirection> = {
    N: "up",
    E: "right",
    S: "down",
    W: "left",
  };
  const dir = directionMap[moveParts[3]];
  if (!dir) return null;

  return {
    raw: move as string,
    car: carId,
    color: cars.value.get(carId)!.color,
    length: moveLength,
    direction: dir,
    currentMove: false,
  };
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
</script>
