
import type { BaseCar, BoardExchange } from "@/composables/useRushHour";

let carNameCounter = 0;

export function serializeBoard(
    board : BoardExchange
) {
    const {gridSizeX, gridSizeY, cars, targetCar} = board;
    let serializedBoard = `${gridSizeX},${gridSizeY}$`, exitPosition;
    if (targetCar.orientation === "horizontal") {
        exitPosition = "0," + (targetCar.y + 1);
    } else {
        exitPosition = (targetCar.x + 1) + ",0";
    }
    serializedBoard = serializedBoard + exitPosition + ";";
    const serializedCars: string[] = [];

    const carNames: Map<string, number> = new Map();
    carNameCounter = 0;
    for (const car of cars.values()) {
        const carName = getCarName(car, targetCar);
        carNames.set(carName, car.id);
        serializedCars.push(
            carName
            // Car positions in the solve backend are 1-indexed, and they are 0-indexed in the front-end
            + (car.x + 1) + "," + (car.y + 1)
            + (car.orientation === "horizontal" ? "R" : "D")
            + car.length
        );
    }
    serializedBoard = serializedBoard + serializedCars.join("|");
    return { carNames, serializedBoard };
}

function getCarName(car: BaseCar, targetCar?: BaseCar | null): string {
    const alphabet = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    if (targetCar && targetCar.id === car.id) {
        // The target car must be called 'r';
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

    return name;
}
