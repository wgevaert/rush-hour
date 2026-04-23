import type { BaseCar, Move, MoveDirection, BoardExchange } from "@/composables/useRushHour";
import { instanceOfUnsolvable } from "@/composables/useRushHour";
import { callApiEndpoint } from "@/composables/httpApi"
import { serializeBoard } from "@/composables/boardSerializer"


export async function fetchSolution(
    board : BoardExchange,
    solveAbortSignal?: AbortSignal
) {
    const { cars } = board;
    const { serializedBoard, carNames } = serializeBoard(board);
    if (!serializedBoard) throw { reason: "Serialization error" }
    try {
        const response = await callApiEndpoint(
            { action: 'solve', 'board': serializedBoard },
            solveAbortSignal
        );
        return handleSolveResponse(response, carNames, cars);
    } catch (failReason) {
        if (instanceOfUnsolvable(failReason)) {
            throw failReason;
        } else if (failReason instanceof Error && failReason.name === "AbortError") {
            throw { reason: "aborted" };
        } else {
            console.error("Solve endpoint failed with reason %O", failReason);
            throw { reason: "Endpoint failure" };
        }
    }
}

function handleSolveResponse(
    response: any,
    carNames: Map<string, number>,
    cars: Map<number, BaseCar>
): Array<Move> {
    if (!response.solved) {
        if (!response.reason && !response.error) {
            console.error("Unexpected response: %O", response);
            throw { reason: "Unexpected endpoint response" };
        }
        const reason = response.reason || "An error occurred";
        throw { reason };
    }
    const solution: Array<Move> = [];
    if (response.moves && response.moves.length) {
        for (const move of response.moves) {
            const parsedMove = parseMove(move, carNames, cars);
            if (parsedMove) {
                solution.push(parsedMove);
            }
        }
    } else {
        // If no moves are needed to solve the puzzle, it must be already solved
        throw { reason: "Puzzle is already solved" };
    }
    const firstMove = solution[0];
    if (firstMove) {
        firstMove.currentMove = true;
    }
    return solution;
}

function parseMove(move: any, carNames: Map<string, number>, cars: Map<number, BaseCar>): Move {
    if (typeof move !== "string" && !(move instanceof String)) {
        throw new Error("Move serialization error: no string");
    }
    const moveRegexp = /^([a-zA-Z]+)([0-9]+)([NESW])$/;
    const moveParts = (move as string).match(moveRegexp);
    if (!moveParts || !moveParts[1] || !moveParts[2] || !moveParts[3]) {
        throw new Error("Move serialization error: not a regexp match");
    }
    if (!carNames.has(moveParts[1])) {
        throw new Error("Move serialization error: nonexistent car name");
    }
    const carId = carNames.get(moveParts[1])!;
    if (!cars.has(carId)) {
        throw new Error("Move serialization error: car name references nonexistent car");
    }
    const moveLength = parseInt(moveParts[2], 10);
    if (isNaN(moveLength)) {
        throw new Error("Move serialization error: no move length");
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
        length: moveLength,
        direction,
        currentMove: false,
    };
}
