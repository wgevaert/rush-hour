import type {BaseCar, Coordinate, Rectangle } from "@/composables/useRushHour"
import { getCarLengthX, getCarLengthY } from "@/composables/useRushHour"

export function collides(car: BaseCar, newPos: Coordinate, cars: Iterable<BaseCar>): boolean {
    const carTrajectoryCells = getTrajectoryCells(car, newPos);
    for (const other of cars) {
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

function getCells(car: BaseCar) {
    const betweenCells: Rectangle = {
        startX: car.x,
        endX: car.x + getCarLengthX(car),
        startY: car.y,
        endY: car.y + getCarLengthY(car),
    }
    return getCellsBetween(betweenCells);
}

function getTrajectoryCells(car: BaseCar, newPos: Coordinate) {
    const betweenCells: Rectangle = {
        startX: Math.min(newPos.x, car.x),
        endX: Math.max(newPos.x, car.x) + getCarLengthX(car),
        startY: Math.min(newPos.y, car.y),
        endY: Math.max(newPos.y, car.y) + getCarLengthY(car),
    };

    return getCellsBetween(betweenCells);
}

function getCellsBetween(betweenCells: Rectangle) {
    const result: { x: number; y: number }[] = [];
    for (let x = betweenCells.startX; x < betweenCells.endX; x++) {
        for (let y = betweenCells.startY; y < betweenCells.endY; y++) {
            result.push({ x, y })
        }
    }
    return result;
}