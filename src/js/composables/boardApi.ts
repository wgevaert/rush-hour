import type { BoardExchange, BaseCar } from "@/composables/useRushHour";
import { getCarLengthX, getCarLengthY } from "@/composables/useRushHour";
import { callApiEndpoint, setUrlParam } from "@/composables/httpApi"
import { serializeBoard } from "@/composables/boardSerializer"

export async function fetchBoard(id?: string): Promise<BoardExchange | null> {
    try {
        const params: Record<string, string> = { action: 'fetchBoard' };
        if (id) {
            params.id = id;
            // We also set the board in the URL, such that we start at the current puzzle again when page is reloaded.
            setUrlParam('board',id);
        }
        const response = await callApiEndpoint(params);
        return handleFetchBoardResponse(response);
    } catch (failReason) {
        console.error("Could not fetch a board with reason %O", failReason);
        return null;
    }
}

export async function postSaveBoard(
    board: BoardExchange
): Promise<string | null> {
    const { serializedBoard } = serializeBoard(board);
    try {
        const response = await callApiEndpoint({ action: 'storeBoard', board: serializedBoard });
        return handleSaveResponse(response);
    } catch (failReason) {
        console.error("Could not save board for reason %O", failReason);
        return null;
    }
}

function handleSaveResponse(response: any): string | null {
    if (response?.success && response?.id) {
        // We also set the board in the URL, such that we start at the current puzzle again when the page is reloaded.
        setUrlParam('board',response.id);
        return response.id;
    }
    return null;
}

function handleFetchBoardResponse(response: any): BoardExchange {
    if (!response || !response?.board) {
        throw { reason: "No board found in api response" };
    }
    return parseBoardString(response.board);
}

function parseBoardString(boardString: string): BoardExchange {
    const { sizeString, exitString, carsString } = splitBoardString(boardString);
    const [gridSizeX, gridSizeY] = parseCoordinate(sizeString);
    const [exitX, exitY] = parseCoordinate(exitString);
    const { cars, targetCarId } = parseCars(carsString);
    const targetCar = flipCarsIfNeeded(
        cars,
        targetCarId,
        // exit position is 1-based in backend and 0-based in frontend.
        exitX - 1,
        exitY - 1,
        gridSizeX,
        gridSizeY
    );
    return {
        gridSizeX,
        gridSizeY,
        cars,
        targetCar,
    };
}

/**
 * Mirrors the entire board if the exit is not placed in the top row or left column.
 *
 * In the backend the exit can be anywhere on the border.
 * In the frontend the exit is always on the board in the top row or the left column, in line with the target car.
 *
 * This function adjusts the cars on the board to make sure the exit is as required by the frontend,
 * or throws if this is not possible. 
 * 
 * @param cars The cars that might have to be flipped.
 * @param targetCarId The id of the target car, which determines where the exit should be placed.
 * @param exitX The x-coordinate of the exit as provided by the backend.
 * @param exitY The y-coordinate of the exit as provided by the backend.
 * @param gridSizeX The size of the board in the X direction.
 * @param gridSizeY The size of the board in the Y direction.
 * @throws Error If there is no target car, or if the target car and exit don't line up.
 * @returns BaseCar The target car.
 */
function flipCarsIfNeeded(
    cars: Map<number, BaseCar>,
    targetCarId: number,
    exitX: number,
    exitY: number,
    gridSizeX: number,
    gridSizeY: number
): BaseCar {
    const targetCar: BaseCar | null = cars.get(targetCarId) || null;
    if (targetCar === null) {
        throw new Error("How can this happen, I set it just a second ago?");
    }
    let flipX: boolean = false, flipY: boolean = false;
    if (targetCar.orientation === 'horizontal') {
        if (exitX != -1) {
            flipX = true;
        }
        if (exitY != targetCar.y) {
            throw new Error("Exit wrongly placed");
        }
    } else {
        if (exitY != -1) {
            flipY = true;
        }
        if (exitX != targetCar.x) {
            throw new Error("Exit wrongly placed");
        }
    }
    for (const car of cars.values()) {
        if (flipX) {
            car.x = gridSizeX - car.x - getCarLengthX(car);
        }
        if (flipY) {
            car.y = gridSizeY - car.y - getCarLengthY(car);
        }
    }
    return targetCar;
}

function splitBoardString(boardString: string) {
    const [sizeString, remainingString] = splitOnChar(boardString, '$');
    const [exitString, carsString] = splitOnChar(remainingString, ';')
    return { sizeString, exitString, carsString };
}

function parseCars(carsString: string) {
    let idCounter = 1, targetCarId;
    const cars: Map<number, BaseCar> = new Map();
    for (const carString of carsString.split('|')) {
        const [name, position, orientation, length] = splitCarString(carString);
        const [x, y] = parseCoordinate(position);
        const car: BaseCar = {
            id: idCounter++,
            // php backend uses 1-indexed but frontend uses 0-indexed.
            x: x - 1,
            y: y - 1,
            length: parseInt(length, 10),
            orientation: orientation == 'D' ? 'vertical' : 'horizontal',
        }
        cars.set(car.id, car);
        // php backend uses the special car name 'r' to indicate the target car.
        if (name === 'r') {
            targetCarId = car.id
        }
    }
    if (typeof targetCarId === 'undefined') {
        throw new Error("No target car found");
    }
    return { cars, targetCarId };
}

function splitCarString(carString: string) {
    const carRegex = /^([^0-9]+)([1-9][0-9]*,[1-9][0-9]*)([DR])([0-9]+)$/;
    const matches = carString.match(carRegex);
    if (matches === null || matches.length < 5) {
        throw new Error("Board serialization error");
    }
    const name = matches[1], position = matches[2], orientation = matches[3], length = matches[4];

    return [name, position, orientation, length]
}

function parseCoordinate(coordinate: string) {
    const [xString, yString] = splitOnChar(coordinate, ',');
    const x = parseInt(xString, 10), y = parseInt(yString, 10);
    if (isNaN(x) || isNaN(y)) {
        throw new Error("Board serialization error")
    }
    return [x, y];
}

function splitOnChar(sourceString: string, splitChar: string) {
    const parts = sourceString.split(splitChar, 2);
    if (parts.length < 2) {
        throw new Error("Board serialization error");
    }
    return parts;
}