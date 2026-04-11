<template>
  <div class="app">
    <aside class="controls">
      <h2>Rush Hour Controls</h2>

      <button :disabled="solvingPuzzle" @click="toggleEditMode()">
        {{ editMode ? "Play puzzle" : "Edit puzzle" }}
      </button>
      <template v-if="editMode">
        <h3> Edit puzzle</h3>
        <button @click="addCar">Add Car</button>
        <button @click="clearBoard">Clear Board</button>
        <button :disabled="!selectedCar" @click="rotateSelectedCar">Rotate Car</button>
        <button :disabled="!selectedCar" @click="lengthenSelectedCar">Lengthen Car</button>
        <button :disabled="!selectedCar" @click="shortenSelectedCar">Shorten Car</button>
        <button :disabled="!selectedCar" @click="removeSelectedCar">Delete Car</button>
        <button :disabled="!selectedCar" @click="makeSelectedTarget">Make Car Objective</button>
      </template>
      <template v-if="!editMode">
        <h3> Play puzzle</h3>
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
          v-for="car in cars.values()"
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
          @mousedown="startExitDrag"
        />
      </g>
    </svg>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";

const gridSizeX = ref(6);
const gridSizeY = ref(6);
const cellSize = 100;
const boardSizeX = ref(gridSizeX.value * cellSize);
const boardSizeY = ref(gridSizeY.value * cellSize);
const viewBox = ref(`0 0 ${boardSizeX.value} ${boardSizeY.value}`)

const editMode = ref(true);

type CarOrientation = "horizontal" | "vertical";
type MoveDirection = "up" | "right" | "down" | "left";

interface Car {
  id: number,
  x: number,
  y: number,
  originalX: number,
  originalY: number,
  length: number,
  orientation: CarOrientation,
  color: string,
}

interface Move {
  string: string,
  car: number,
  color: string,
  length: number,
  direction: moveDirection,
  currentMove: bool,
}

interface Unsolvable {
  reason: string
}

const cars = ref(new Map());
const selectedCar = ref(null);
const targetCar = ref(null);

const hasWon = ref(false);
const solution = ref([]);
const unsolvable = ref<Unsolvable|false>(false);
const retrievingSolution = ref(false);
const solvingPuzzle = ref(false);
const playingSolution = ref(false);
let solveAbortController;

let idCounter = 1;
let carNameCounter = 0;
let carNames = new Map();

/* Drag state */
const dragging = ref(false);
const dragCar = ref(null);
const dragStart = ref({ x: 0, y: 0 });
const carStart = ref({ x: 0, y: 0 });

/* Grid */
const cells = computed(() => {
  const result = [];
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
  cars.value.values().forEach((car) => {
    car.originalX = car.x, car.originalY = car.y
  });
  unsolvable.value = false;
}

/* Add car */
function addCar() {
  const newCar : Car = {
    id: idCounter++,
    x: 0,
    y: 0,
    originalX: 0,
    originalY: 0,
    length: 2,
    orientation: 'horizontal',
    color: randomColor(idCounter),
  };
  cars.value.set(newCar.id, newCar);
  selectCar(newCar);
}

function getStroke(car : Car): string {
  if (targetCar.value && targetCar.value.id === car.id) {
     return selectedCar.value?.id === car.id ? 'red' : 'darkred';
  }
  return  selectedCar.value?.id === car.id ? 'black' : 'none';
}
/* Select */
function selectCar(car : Car) {
  selectedCar.value = car;
}

function removeSelectedCar() {
  cars.value.delete(selectedCar.value.id);
  if ( targetCar.value.id === selectedCar.value.id ) {
    targetCar.value = null;
  }
  selectedCar.value = null;
}
function rotateSelectedCar() {
  selectedCar.value.orientation = selectedCar.value.orientation === 'horizontal' ? 'vertical' : 'horizontal';
}
function lengthenSelectedCar() {
  const car = selectedCar.value;
  if (
    (car.orientation === 'horizontal' && car.length + car.x < gridSizeX.value) ||
    (car.orientation === 'vertical' && car.length + car.y < gridSizeY.value)
  )
    car.length++;
}
function shortenSelectedCar() {
  if (selectedCar.value.length > 1)
    selectedCar.value.length--;
}
function makeSelectedTarget() {
  targetCar.value = selectedCar.value;
}

/* DRAG START */
function startDrag(event, car) {
  if (solvingPuzzle.value)
    return;
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
function onDrag(event) {
  if (!dragging.value || !dragCar.value) return;

  const dx = event.clientX - dragStart.value.x;
  const dy = event.clientY - dragStart.value.y;

  const car = dragCar.value;

  let newX = carStart.value.x;
  let newY = carStart.value.y;

  if (editMode.value) {
    newX = Math.round((carStart.value.x * cellSize + dx) / cellSize);
    newY = Math.round((carStart.value.y * cellSize + dy) / cellSize);

    const maxX = gridSizeX.value - (car.orientation == 'horizontal' ? car.length : 1);
    const maxY = gridSizeY.value - (car.orientation == 'vertical' ? car.length : 1);

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
  if ( dragStart.value.x !== dragCar.value.x || dragStart.value.y !== dragCar.value.y ) {
    invalidateSolution();
  }
  dragging.value = false;
  dragCar.value = null;
  if ( targetCar.value === selectedCar.value ) {
    checkWin();
  }

  window.removeEventListener("mousemove", onDrag);
  window.removeEventListener("mouseup", stopDrag);
}

/* Collision detection */
function collides(car, newX, newY) {
  return cars.value.values().some((other) => {
    if (other.id === car.id) return false;

    const carCells = getCells(car, newX, newY);
    const otherCells = getCells(other, other.x, other.y);

    return carCells.some((c1) =>
      otherCells.some((c2) => c1.x === c2.x && c1.y === c2.y)
    );
  });
}

function getCells(car, x, y) {
  const cells = [];
  for (let i = 0; i < car.length; i++) {
    if (car.orientation === "horizontal") {
      cells.push({ x: x + i, y });
    } else {
      cells.push({ x, y: y + i });
    }
  }
  return cells;
}

function checkWin() {
  if ( !targetCar.value ) {
    hasWon.value = false;
    return;
  }
  const car = targetCar.value;
  if (car.orientation === 'horizontal') {
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
  cars.value.values().forEach((car) => {
    car.x = car.originalX;
    car.y = car.originalY;
  });
  hasWon.value = false;
  solution.value = [];
  unsolvable.value = false;
}


function clearBoard() {
  if ( !cars.value.size || window.confirm("Are you sure that you want to clear all cars from the board?") ) {
    cars.value = new Map;
    selectedCar.value = null;
    targetCar.value = null;
    idCounter = 1;
    resetBoard();
  }
}

function solvePuzzle() {
  if (retrievingSolution.value)
    return;

  const serializedBoard = serializeBoard();
  if ( !serializedBoard )
    return;

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
    body: new URLSearchParams({ action: 'solve', 'board': serializedBoard }),
    signal: solveAbortController.signal,
  }).then((response) => {
    retrievingSolution.value = false;
    response.json().then(
      (parsedResponse) => handleSolveResponse(parsedResponse),
      (failReason) => {
        solvingPuzzle.value = false;
        unsolvable.value = { reason: 'Endpoint json failure' };
        console.error('Solve endpoint response could not be interpreted as json with reason %O', failReason);
      }
    );

  }, (failReason) => {
    retrievingSolution.value = false;
    solvingPuzzle.value = false;
    if ( failReason === "user-abort" ) {
      unsolvable.value = { reason: 'aborted' };
    } else {
      unsolvable.value = { reason: 'Endpoint failure' };
      console.error('Solve endpoint failed with reason %O', failReason);
    }
  });
}

function serializeBoard() {
  let serializedBoard = gridSizeX.value + ',' + gridSizeY.value + '$'
  if ( !targetCar.value ) {
    unsolvable.value = { reason: "Cannot solve without objective" };
    return false;
  }
  if ( targetCar.value.orientation === 'horizontal' ) {
    serializedBoard = serializedBoard + '0,' + (targetCar.value.y + 1)
  } else {
    serializedBoard = serializedBoard + (targetCar.value.x + 1) + ',0';
  }
  serializedBoard = serializedBoard + ';';
  const serializedCars = [];

  carNames.clear();
  carNameCounter = 0;
  cars.value.forEach((car) => {
    serializedCars.push(getCarName(car) + (car.x + 1)+ ',' + (car.y+1) + (car.orientation === 'horizontal' ? 'R' : 'D') + car.length);
  });
  serializedBoard = serializedBoard + serializedCars.join('|');
  return serializedBoard;
}

function getCarName(car) {
  const alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
  if ( targetCar.value && targetCar.value.id == car.id ) {

    // The target car must be called 'r';
    carNames.set('r', car.id);
    return 'r';
  }
  if ( carNameCounter < alphabet.length && alphabet.charAt(carNameCounter) === 'r' ) {
    // A car that is not the target car must not be called 'r';
    carNameCounter++;
  }
  let value = carNameCounter++;
  let name = '';
  do {
    const remainder = value % alphabet.length;
    name = name + alphabet.charAt(remainder);
    value = ( value - remainder ) / alphabet.length;
  } while(value != 0);

  carNames.set(name, car.id);
  return name;
}

function handleSolveResponse( response ) {
  if ( !response.solved ) {
    const reason = response.reason || ( response.error ? 'An error occurred' : 'Puzzle is not solvable' );
    unsolvable.value = { reason };
    return;
  }
  if ( response.moves ) {
    for(const move of response.moves ) {
      const parsedMove = parseMove(move);
      if ( parsedMove ) {
        solution.value.push( parsedMove );
      }
    }
  }
  const firstMove = solution.value[0];
  if (firstMove) {
    firstMove.currentMove = true;
  }
}

function parseMove( move : any ): Move {
  if (typeof move !== 'string' && !move instanceof String) {
    return null;
  }
  const moveRegexp = /^([a-zA-Z]+)([0-9]+)([NESW])$/
  const moveParts = move.match(moveRegexp);
  if ( !moveParts[1] || !moveParts[2] || !moveParts[3] ) {
    return null;
  }
  if ( !carNames.has(moveParts[1]) ) {
    return null;
  }
  const carId = carNames.get(moveParts[1]);
  if ( !cars.value.has(carId) ) {
    return null;
  }
  const car = cars.value.get(carId);
  const moveLength = parseInt(moveParts[2], 10);
  if ( isNaN( moveLength ) ) {
    return null;
  }
  const directionMap = {
    N: 'up',
    E: 'right',
    S: 'down',
    W: 'left',
  };
  return {
    string: move,
    car: carId,
    color: cars.value.get(carId).color,
    length: moveLength,
    direction: directionMap[moveParts[3]],
    currentMove: false,
  };
}
function playSolve() {
  if ( playingSolution.value ) {
    return;
  }
  playingSolution.value = true;
  const currentStepIndex = solution.value.findIndex((move) => move.currentMove) ?? 0;
  playSolveSteps(currentStepIndex);
}
function playSolveSteps( currentStepIndex : number ) {
  if (playingSolution.value === false ) {
    // We are paused or something.
    return;
  }
  if(solution.value.length <= currentStepIndex) {
    console.error( "solution index out of bounds" );
    return;
  }
  const currentMove = solution.value[currentStepIndex];
  const movingCar = cars.value.get(currentMove.car);
  selectCar(movingCar);
  moveSelectedCar(currentMove.length, currentMove.direction);
  setTimeout(() => {
    currentMove.currentMove = false;
    const nextMove = solution.value[currentStepIndex + 1];
    if ( nextMove ) {
      nextMove.currentMove = true;
      playSolveSteps(currentStepIndex + 1);
    } else {
      playingSolution.value = false;
      checkWin();
    }
  }, 300);
}
function pauseSolve() {
  playingSolution.value = false;
}

function moveSelectedCar(length : number, direction : MoveDirection) {
  switch(direction) {
    case 'up': return moveSelectedCarUp(length);
    case 'right': return moveSelectedCarRight(length);
    case 'down': return moveSelectedCarDown(length);
    case 'left': return moveSelectedCarLeft(length);
  }
}
function moveSelectedCarUp(length : number) {
  selectedCar.value.y -= length;
}
function moveSelectedCarDown(length : number) {
  selectedCar.value.y += length;
}
function moveSelectedCarLeft(length : number) {
  selectedCar.value.x -= length;
}
function moveSelectedCarRight(length : number) {
  selectedCar.value.x += length;
}

function stopSolve() {
  if (solveAbortController) {
    solveAbortController.abort('user-abort');
  }
  retrievingSolution.value = false;
  solvingPuzzle.value = false;
  unsolvable.value = false;
  solution.value = [];
}
function invalidateSolution() {
  solvingPuzzle.value = false;
}

function randomColor(id: number): string {
  const angle = (id * 42.5)%360;
  return `hsl(${angle}, 70%, 60%)`;
}

</script>

<style>
@media (prefers-color-scheme: dark) {
  :root {
    --background: #322;
    --highlight: #433;
  }
}

@media (prefers-color-scheme: light) {
  :root {
    --background: #ecc;
    --highlight: #dbb;
  }
}

.app {
  display: flex;
  font-family: sans-serif;
}

.controls {
  width: 200px;
  padding: 10px;
  background: var(--background);
}

.board {
  border: 2px solid #333;
  margin-left: 10px;
}

#exit {
  pointer-events: none;
}

.solution-info {
  text-align: left;
  border: 2px solid #333;
  height: 10em;
  overflow-y: auto;
  white-space: wrap;
}
.solve-step {
  display: inline-block;
  margin-right: 0.5em;
}
.active-solve-step {
  background: var(--highlight);
}

button {
  display: block;
  margin: 5px 0;
  padding: 8px;
  width: 100%;
}

.win-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.6);

  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;

  z-index: 2;
}
.win-overlay button.cancel {
  font-size: 0.5em;
  position: absolute;
  top: 1.5em;
  right: 1.5em;
  width: auto;
}

.win-box {
  position: relative;
  padding: 2em;
  border-radius: 12px;
  text-align: center;
  animation: pop 1s ease;
  background: var(--background)
}

.win-box h1 {
  margin-bottom: 0.5em;
}

@keyframes pop {
  0% {
    transform: scale(0.5);
    opacity: 0;
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
