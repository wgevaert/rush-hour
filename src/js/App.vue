<template>
  <div class="app">
    <aside class="controls">
      <h2>Rush Hour Controls</h2>

      <button :disabled="solvingPuzzle" @click="toggleEditMode()">
        {{ editMode ? "Play puzzle" : "Edit puzzle" }}
      </button>
      <template v-if="editMode">
        <h3>Edit puzzle</h3>
        <section>
          <button @click="addCar">Add Car</button>
          <button @click="clearBoard">Clear Board</button>
          <button :disabled="!selectedCar" @click="rotateSelectedCar">Rotate Car</button>
          <button :disabled="!selectedCar" @click="lengthenSelectedCar">Lengthen Car</button>
          <button :disabled="!selectedCar" @click="shortenSelectedCar">Shorten Car</button>
          <button :disabled="!selectedCar" @click="removeSelectedCar">Delete Car</button>
          <button :disabled="!selectedCar" @click="makeSelectedTarget">Make Car Objective</button>
        </section>
        <section>
          <button @click="showSaveLoadModal()">Import/Export</button>
          <dialog ref="saveLoadModal" @click="onSaveLoadModalClick($event)">
            <textarea v-model="saveLoadInput"></textarea>
            <button @click="saveBoard">Export</button>
            <button @click="loadBoard">Import</button>
          </dialog>
        </section>
      </template>
      <template v-if="!editMode">
        <h3>Play puzzle</h3>
        <button @click="resetBoard">Reset</button>
        <button @click="solvePuzzle">Solve</button>
        <article class="solution-box">
          <section class="solution-info">
            <p v-if="retrievingSolution">Retrieving Solution</p>
            <p v-if="unsolvable">Could not solve puzzle. <template v-if="unsolvable.reason">Reason: {{unsolvable.reason}}</template></p>
            <p v-if="solution"><template v-for="move in solution"><span class="solve-step" :class="move.currentMove ? 'active-solve-step' : ''"><span :style="{'color': getMoveColor(move)}">{{move.car}}</span>{{move.direction}}{{move.length}}</span></template></p>
          </section>
          <section v-if="solvingPuzzle" class="solution-buttons">
            <button v-if="!playingSolution" @click="playSolve">Play</button>
            <button v-if="playingSolution" @click="pauseSolve">Pause</button>
            <button @click="playSingleSolveStep">Step</button>
            <button @click="stopSolve">Cancel</button>
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
          :width="cellSize"
          :height="cellSize"
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
          :width="getCarLengthX(car) * cellSize"
          :height="getCarLengthY(car) * cellSize"
          :fill="car.color"
          rx="10"
          @mousedown="startDrag($event, car)"
          @click="selectCar(car)"
          :stroke="getStroke(car)"
          stroke-width="4"
        />
      </g>

      <!-- EXIT -->
      <defs>
        <!-- A marker to be used as an arrowhead -->
        <marker
          id="arrow"
          viewBox="0 0 10 10"
          refX="5"
          refY="5"
          markerWidth="6"
          markerHeight="6"
          orient="auto-start-reverse">
          <path d="M 0 0 L 10 5 L 0 10 z" />
        </marker>
      </defs>
      <g id="exit">
        <line
          v-if="targetCar"
          :x1="(getExitX() + targetCar?.orientation === 'horizontal' ? 1/4 : 1/2 )*cellSize"
          :x2="(getExitX() + targetCar?.orientation === 'horizontal' ? 3/4 : 1/2 )*cellSize"
          :y1="(getExitY() + targetCar?.orientation === 'vertical' ? 3/4 : 1/2 )*cellSize"
          :y2="(getExitY() + targetCar?.orientation === 'vertical' ? 3/4 : 1/2 )*cellSize"
          orient="auto"
          fill="gold"
          opacity="0.5"
          stroke="orange"
          marker-end="url(#arrow)"
        />
      </g>
    </svg>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { useRushHour, type Car, type Move, getCarLengthX, getCarLengthY } from "@/composables/useRushHour";

function getStroke(car: Car): string {
  if (targetCar.value && targetCar.value.id === car.id) {
    return selectedCar.value?.id === car.id ? "red" : "darkred";
  }
  return selectedCar.value?.id === car.id ? "black" : "none";
}

function getMoveColor(move: Move): string {
  if (cars.value.has(move.car)) {
    return cars.value.get(move.car)!.color;
  }
  return 'grey';
}

const saveLoadModal = ref<HTMLDialogElement|null>(null);

function showSaveLoadModal() {
  saveLoadModal.value?.showModal();
}

function closeSaveLoadModal() {
  saveLoadModal.value?.close();
}

function onSaveLoadModalClick(event: PointerEvent) {
  const dialog = saveLoadModal.value;
  if (!dialog) return;
  const rect = dialog.getBoundingClientRect();
  const isInDialog = 
    event.clientX >= rect.left &&
    event.clientX <= rect.right &&
    event.clientY >= rect.top &&
    event.clientY <= rect.bottom;

  if (!isInDialog) {
    closeSaveLoadModal();
  }
}

const viewBox = computed(() => `0 0 ${boardSizeX.value} ${boardSizeY.value}`);

const {
  cellSize,
  boardSizeX,
  boardSizeY,
  cells,
  editMode,
  hasWon,
  solution,
  unsolvable,
  retrievingSolution,
  solvingPuzzle,
  playingSolution,
  selectedCar,
  cars,
  targetCar,
  getExitX,
  getExitY,
  saveLoadInput,
  saveBoard,
  loadBoard,
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
  loadBoardFromUrl,
} = useRushHour();

loadBoardFromUrl();
console.log(getExitX(),getExitY());
</script>
