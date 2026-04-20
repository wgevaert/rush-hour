import type { Car, Move, MoveDirection } from "@/composables/useRushHour";

let carNameCounter = 0;

export async function fetchSolution(
    gridSizeX: number,
    gridSizeY: number,
    cars: Map<number, Car>,
    targetCar?: Car | null,
    solveAbortSignal?: AbortSignal
) {
    const { serializedBoard, carNames } = serializeBoard(gridSizeX, gridSizeY, cars, targetCar);
    if (!serializedBoard) return;
    return fetch("/api.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
        },
        body: new URLSearchParams({ action: "solve", board: serializedBoard }),
        signal: solveAbortSignal,
    })
        .then((response) => {
            return response.json().then(
                (parsedResponse) => handleSolveResponse(parsedResponse, carNames, cars),
                (failReason) => {
                    console.error("Solve endpoint response could not be interpreted as json with reason %O", failReason);
                    throw { reason: "Endpoint json failure" };
                }
            );
        })
        .catch((failReason) => {
            if (failReason && failReason.name === "AbortError") {
                throw { reason: "aborted" };
            } else {
                console.error("Solve endpoint failed with reason %O", failReason);
                throw { reason: "Endpoint failure" };
            }
        });
}

function serializeBoard(gridSizeX:number, gridSizeY: number, cars: Map<number, Car>, targetCar?: Car|null) {
    let serializedBoard = `${gridSizeX},${gridSizeY}$`;
    if (!targetCar) {
        throw { reason: "Cannot solve without objective" };
    }
    if (targetCar.orientation === "horizontal") {
        serializedBoard = serializedBoard + "0," + (targetCar.y + 1);
    } else {
        serializedBoard = serializedBoard + (targetCar.x + 1) + ",0";
    }
    serializedBoard = serializedBoard + ";";
    const serializedCars: string[] = [];

    const carNames : Map<string, number> = new Map();
    carNameCounter = 0;
    for (const car of cars.values()) {
        const carName = getCarName(car, targetCar);
        carNames.set(carName, car.id);
        serializedCars.push(carName + (car.x + 1) + "," + (car.y + 1) + (car.orientation === "horizontal" ? "R" : "D") + car.length);
    }
    serializedBoard = serializedBoard + serializedCars.join("|");
    return { carNames, serializedBoard };
}

function getCarName(car: Car, targetCar?: Car | null): string {
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

function handleSolveResponse(response: any, carNames: Map<string, number>, cars: Map<number, Car>): Array<Move> {
    if (!response.solved) {
        const reason = response.reason || (response.error ? "An error occurred" : "Puzzle is not solvable");
        throw { reason };
    }
    const solution: Array<Move> = [];
    if (response.moves) {
        for (const move of response.moves) {
            const parsedMove = parseMove(move, carNames, cars);
            if (parsedMove) {
                solution.push(parsedMove);
            }
        }
    }
    const firstMove = solution[0];
    if (firstMove) {
        firstMove.currentMove = true;
    }
    return solution;
}

function parseMove(move: any, carNames: Map<string, number>, cars: Map<number, Car>): Move {
    if (typeof move !== "string" && !(move instanceof String)) {
        throw new Error("Move serialization error: no string");
    }
    const moveRegexp = /^([a-zA-Z]+)([0-9]+)([NESW])$/;
    const moveParts = (move as string).match(moveRegexp);
    if (!moveParts || !moveParts[1] || !moveParts[2] || !moveParts[3]) {
        throw new Error("Move serialization error: no string");
    }
    if (!carNames.has(moveParts[1])) {
        throw new Error("Move serialization error: no string");
    }
    const carId = carNames.get(moveParts[1])!;
    if (!cars.has(carId)) {
        throw new Error("Move serialization error: no string");
    }
    const moveLength = parseInt(moveParts[2], 10);
    if (isNaN(moveLength)) {
        throw new Error("Move serialization error: no string");
    }
    const directionMap: Record<string, MoveDirection> = {
        N: "up",
        E: "right",
        S: "down",
        W: "left",
    };
    const direction = directionMap[moveParts[3]];
    if (!direction)
        throw new Error("Move serialization error: unknown direction");
    return {
        raw: move as string,
        car: carId,
        color: cars.get(carId)!.color,
        length: moveLength,
        direction,
        currentMove: false,
    };
}
