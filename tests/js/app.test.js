import { describe, it, expect } from 'vitest'
import { useRushHour } from '@/composables/useRushHour'

describe('RushHour - addCar', () => {
  it('adds a car to the board', () => {
    const { cars, addCar } = useRushHour()

    addCar()

    expect(cars.value.size).toBe(1)
  })
})