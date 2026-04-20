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
import { useRushHour } from "@/composables/useRushHour";

const {
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
} = useRushHour();
</script>
