# A Rush Hour Web-app

Includes a solver and a visualiser.

## Usage:
### Via Command Line Interface:

```sh
php rushHourCli.php < input-board.txt
```

Example for `input-board.txt`:
```
....AA
..BBCC
rr..EF
GGHHEF
...IEF
...IJJ
```

### Via Web:
1. Build the front-end app: `npm install` and `npm run build`.
2. Copy the `config.json.sample` file to `config.json`, and adjust as necessary.
3. Set up your web server to serve from `dist/`, and correctly handle php files.
4. Access the interactive solver at `index.html` and the solver api at `api.php`

#### api.php
The api accepts form-encoded input and gives json-encoded output. Each api call requires a parameter 'action', which determines what will happen:

+--------+----------------------+-------------------------------------------------------------------------------------+
| Action | Extra parameters     | Description                                                                         |
+--------+----------------------+-------------------------------------------------------------------------------------+
| solve  | board, serialization | Gives a sequence of moves that solves the provided board.                           |
+--------+----------------------+-------------------------------------------------------------------------------------+
| draw   | board, serialization | Gives an ascii-representation of the provided board.                                |
+--------+----------------------+-------------------------------------------------------------------------------------+
| fetch  | id                   | Takes an id and turns it into a board if possible.                                  |
|        |                      | Currently only supports ids that completely encode a board.                         |
+--------+----------------------+-------------------------------------------------------------------------------------+
| store  | board, serialization | Takes a board and returns an ID that can be used to retrieve the board again.       |
|        |                      | Currently does not actually store boards, but encodes the board in the returned id. |
+--------+----------------------+-------------------------------------------------------------------------------------+
