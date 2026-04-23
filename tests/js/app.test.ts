import { describe, it, expect, vi } from 'vitest'
import * as httpApi from '@/composables/httpApi'
import { useRushHour } from '@/composables/useRushHour'

describe('RushHour - addCar', () => {
    it('adds a car to the board', () => {
        const { cars, addCar } = useRushHour()

        addCar()

        expect(cars.value.size).toBe(1)
    }),
        it('initially has an id of 1', () => {
            const { cars, addCar } = useRushHour()

            addCar()

            expect(cars.value.has(1)).toBe(true)

        }),
        it('has a default length of 2', () => {
            const { cars, addCar } = useRushHour()

            addCar()
            const car = cars.value.get(1);

            expect(car.length).toBe(2)
        })
})

describe('RushHour - toggleEditMode', () => {
    it('sets edit mode to false', () => {
        const { editMode, toggleEditMode } = useRushHour();

        toggleEditMode();

        expect(editMode.value).toBe(false);
    }),
        it('sets edit mode to true the 2nd time', () => {
            const { editMode, toggleEditMode } = useRushHour();

            toggleEditMode();
            toggleEditMode();

            expect(editMode.value).toBe(true);
        }),
        it('stores originalX and originalY on cars', () => {
            const { addCar, cars, toggleEditMode } = useRushHour();
            addCar();
            const car = cars.value.get(1);
            car.x = 2;
            car.y = 3;
            toggleEditMode();

            expect(car.originalX).toBe(2);
            expect(car.originalY).toBe(3);
        })
});

describe('RushHour - selectCar', () => {
    it('selects the given car', () => {
        const { selectCar, selectedCar, addCar, cars } = useRushHour();
        addCar();
        const car = cars.value.get(1);

        selectCar(car);

        expect(selectedCar.value).toBe(car);
    })
})

describe('RushHour - removeSelectedCar', () => {
    it('removes the selected car', () => {
        const { selectCar, selectedCar, removeSelectedCar, addCar, cars } = useRushHour();
        addCar();
        const car = cars.value.get(1);
        selectCar(car);

        removeSelectedCar();

        expect(selectedCar.value).toBe(null);
        expect(cars.value.has(1)).toBe(false);
    }),
        it('removes the target car if needed', () => {
            const { selectCar, makeSelectedTarget, targetCar, removeSelectedCar, addCar, cars } = useRushHour();
            addCar();
            const car = cars.value.get(1);
            selectCar(car);
            makeSelectedTarget();

            removeSelectedCar();

            expect(targetCar.value).toBe(null);
        })
})

describe('RushHour - rotateSelectedCar', () => {
    it('makes a horizontal car vertical', () => {
        const { selectCar, selectedCar, addCar, cars, rotateSelectedCar } = useRushHour();
        addCar();
        const car = cars.value.get(1);
        selectCar(car);
        selectedCar.value.orientation = "horizontal";

        rotateSelectedCar();

        expect(selectedCar.value.orientation).toBe("vertical");
    }),
        it('makes a vertical car horizontal', () => {
            const { selectCar, selectedCar, addCar, cars, rotateSelectedCar } = useRushHour();
            addCar();
            const car = cars.value.get(1);
            selectCar(car);
            selectedCar.value.orientation = "vertical";

            rotateSelectedCar();

            expect(selectedCar.value.orientation).toBe("horizontal");
        })
})

describe('RushHour - lengthenSelectedCar', () => {
    it('lengthens the selected car', () => {
        const { selectCar, selectedCar, addCar, cars, lengthenSelectedCar } = useRushHour();
        addCar();
        const car = cars.value.get(1);
        selectCar(car);

        lengthenSelectedCar();

        expect(selectedCar.value.length).toBe(3);
    }),
        it('cannot lengthen a horizontal car past max X', () => {
            const { selectCar, selectedCar, gridSizeX, addCar, cars, lengthenSelectedCar } = useRushHour();
            addCar();
            const car = cars.value.get(1);
            selectCar(car);
            selectedCar.value.orientation = "horizontal";
            selectedCar.value.length = 2;
            selectedCar.value.x = gridSizeX - 2;

            lengthenSelectedCar();

            expect(selectedCar.value.length).toBe(2);
        })
    it('cannot lengthen a vertical car past max Y', () => {
        const { selectCar, selectedCar, gridSizeY, addCar, cars, lengthenSelectedCar } = useRushHour();
        addCar();
        const car = cars.value.get(1);
        selectCar(car);
        selectedCar.value.orientation = "vertical";
        selectedCar.value.length = 2;
        selectedCar.value.y = gridSizeY - 2;

        lengthenSelectedCar();

        expect(selectedCar.value.length).toBe(2);
    })
})


describe('RushHour - shortenSelectedCar', () => {
    it('shortens the selected car', () => {
        const { selectCar, selectedCar, addCar, cars, shortenSelectedCar } = useRushHour();
        addCar();
        const car = cars.value.get(1);
        selectCar(car);

        shortenSelectedCar();

        expect(selectedCar.value.length).toBe(1);
    }),
        it('cannot shorten a car of length 1', () => {
            const { selectCar, selectedCar, addCar, cars, shortenSelectedCar } = useRushHour();
            addCar();
            const car = cars.value.get(1);
            selectCar(car);
            selectedCar.value.length = 1;

            shortenSelectedCar();

            expect(selectedCar.value.length).toBe(1);
        })
});


describe('RushHour - makeSelectedTarget', () => {
    it('makes the selected car the target', () => {
        const { selectCar, makeSelectedTarget, targetCar, addCar, cars } = useRushHour();
        addCar();
        const car = cars.value.get(1);
        selectCar(car);

        makeSelectedTarget();

        expect(targetCar.value).toBe(car);
    })
})

describe('RushHour - dismissWin', () => {
    it('sets win to false', () => {
        const { dismissWin, hasWon } = useRushHour();
        hasWon.value = true;

        dismissWin();

        expect(hasWon.value).toBe(false);
    })
});

describe('RushHour - resetBoard', () => {
    it('resets a car to its original position', () => {
        const { selectCar, selectedCar, addCar, cars, resetBoard } = useRushHour();
        addCar();
        const car = cars.value.get(1);
        selectCar(car);
        selectedCar.value.x = 1;
        selectedCar.value.y = 2;
        selectedCar.value.originalX = 3;
        selectedCar.value.originalY = 4;

        resetBoard();

        expect(selectedCar.value.x).toBe(3);
        expect(selectedCar.value.y).toBe(4);
    }),
        it('resets hasWon to false', () => {
            const { resetBoard, hasWon } = useRushHour();
            hasWon.value = true;

            resetBoard();

            expect(hasWon.value).toBe(false);
        })
});

describe('RushHour - clearBoard', () => {
    it('does not ask confirmation on an empty board', () => {
        const confirmSpy = vi.spyOn(window, 'confirm');
        const { clearBoard } = useRushHour();

        clearBoard();

        expect(confirmSpy).toHaveBeenCalledTimes(0);
    }),
        it('Asks for confirmation', () => {
            const confirmSpy = vi.spyOn(window, 'confirm');
            const { clearBoard, addCar } = useRushHour();
            addCar();

            clearBoard();

            expect(confirmSpy).toHaveBeenCalledTimes(1);
        }),
        it('removes all cars on confirm', () => {
            vi.spyOn(window, 'confirm').mockReturnValue(true);
            const { clearBoard, addCar, cars } = useRushHour();
            addCar();

            clearBoard();

            expect(cars.value.size).toBe(0);
        })
    it('does nothing if confirm is dismissed', () => {
        vi.spyOn(window, 'confirm').mockReturnValue(false);
        const { clearBoard, addCar, cars } = useRushHour();
        addCar();

        clearBoard();

        expect(cars.value.size).toBe(1);
    })
})

describe('RushHour - solvePuzzle', () => {
    it('stops early if no target car is set', async () => {
        const apiSpy = vi.spyOn(httpApi, 'callApiEndpoint').mockResolvedValue({
            solved: true,
            moves: [
                "r1W"
            ]
        });
        const { solvePuzzle, addCar, unsolvable } = useRushHour();
        addCar();

        await solvePuzzle();

        expect(apiSpy).toHaveBeenCalledTimes(0);
        expect(unsolvable.value.reason).toBeDefined()
        expect(unsolvable.value.reason).toContain('objective')
    }),
        it('calls the api endpoint', async () => {
            const apiSpy = vi.spyOn(httpApi, 'callApiEndpoint').mockResolvedValue({
                solved: true,
                moves: [
                    "r1W"
                ]
            });
            const { selectCar, makeSelectedTarget, targetCar, solvePuzzle, addCar, cars } = useRushHour();
            addCar();
            const car = cars.value.get(1);
            selectCar(car);
            makeSelectedTarget();
            targetCar.value.x = 2;

            await solvePuzzle();

            expect(apiSpy).toHaveBeenCalledTimes(1);
        }),
        it('provides a serialized board to the api', async () => {
            const apiSpy = vi.spyOn(httpApi, 'callApiEndpoint').mockResolvedValue({
                solved: true,
                moves: [
                    "r1W"
                ]
            });
            const { selectCar, makeSelectedTarget, targetCar, solvePuzzle, addCar, cars } = useRushHour();
            addCar();
            const car = cars.value.get(1);
            selectCar(car);
            makeSelectedTarget();
            targetCar.value.x = 2;

            await solvePuzzle();

            expect(apiSpy).toHaveBeenCalledWith(
                { action: 'solve', board: "6,6$0,1;r3,1R2" },
                expect.any(AbortSignal)
            );
        }),
        it('sets the solution', async () => {
            vi.spyOn(httpApi, 'callApiEndpoint').mockResolvedValue({
                solved: true,
                moves: [
                    "r1E",
                    "r2W",
                ]
            });
            const { selectCar, makeSelectedTarget, targetCar, solvePuzzle, addCar, cars, solution } = useRushHour();
            addCar();
            const car = cars.value.get(1);
            selectCar(car);
            makeSelectedTarget();
            targetCar.value.x = 2;

            await solvePuzzle();

            expect(solution.value).toHaveLength(2);
            expect(solution.value).toStrictEqual([
                expect.objectContaining({car:1, raw:"r1E", direction:'right', length:1}),
                expect.objectContaining({car:1, raw:"r2W", direction:'left', length:2}),
            ]);
        }),
        it('shows given fail reasons to the user', async () => {
            vi.spyOn(httpApi, 'callApiEndpoint').mockResolvedValue({
                solved: false,
                reason: "The chicken crossed the road"
            });
            const { selectCar, makeSelectedTarget, targetCar, solvePuzzle, addCar, cars, unsolvable } = useRushHour();
            addCar();
            const car = cars.value.get(1);
            selectCar(car);
            makeSelectedTarget();
            targetCar.value.y = 2;

            await solvePuzzle();

            expect(unsolvable.value.reason).toBe('The chicken crossed the road');
        }),
        it('handles already solved puzzles', async () => {
            vi.spyOn(httpApi, 'callApiEndpoint').mockResolvedValue({
                solved: true,
                moves: []
            });
            const { selectCar, makeSelectedTarget, targetCar, solvePuzzle, addCar, cars, unsolvable } = useRushHour();
            addCar();
            const car = cars.value.get(1);
            selectCar(car);
            makeSelectedTarget();
            targetCar.value.y = 2;

            await solvePuzzle();

            expect(unsolvable.value.reason).toContain('solved');
        }),
        it('handles unknown errors', async () => {
            vi.spyOn(httpApi, 'callApiEndpoint').mockRejectedValue(
                { error: { message: "something was wrong", code: 0 } }
            );
            const { selectCar, makeSelectedTarget, targetCar, solvePuzzle, addCar, cars, unsolvable } = useRushHour();
            addCar();
            const car = cars.value.get(1);
            selectCar(car);
            makeSelectedTarget();
            targetCar.value.y = 2;

            await solvePuzzle();

            expect(unsolvable.value.reason).toBe('Endpoint failure');
        }),
        it('handles unknown responses', async () => {
            vi.spyOn(httpApi, 'callApiEndpoint').mockResolvedValue(
                { foo: { bar: "something was wrong", baz: 0 } }
            );
            const { selectCar, makeSelectedTarget, targetCar, solvePuzzle, addCar, cars, unsolvable } = useRushHour();
            addCar();
            const car = cars.value.get(1);
            selectCar(car);
            makeSelectedTarget();
            targetCar.value.y = 2;

            await solvePuzzle();

            expect(unsolvable.value.reason).toBe('Unexpected endpoint response');
        })
})
/*
        playSolve,
        pauseSolve,
        stopSolve,
*/
