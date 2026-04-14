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
2. Set up your web server to serve from `dist/`
3. Copy the `config.json.sample` file to `config.json`, and adjust as necessary.
4. Access the interactive solver at index.html and the solver api at `api.php`

