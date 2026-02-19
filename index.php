<?php
/**
 * ============================================================================
 * Author:      Donnel Garner
 * Date:        February 18, 2026
 * Course:      CS480 - Introduction to Artificial Intelligence
 * Institution: Old Dominion University
 * Assignment:  Module 4 — Constraint Satisfaction Problems (CSPs)
 * ============================================================================
 * 
 * PROGRAM DESCRIPTION:
 * This program solves Sudoku puzzles by treating them as Constraint Satisfaction
 * Problems (CSPs). It implements two backtracking algorithms side-by-side so
 * users can load puzzles of varying difficulty (Easy through Evil) and compare
 * how a naive "brute force" approach stacks up against a smarter strategy that
 * uses MRV, LCV, and Forward Checking heuristics from Chapter 6 of our textbook.
 * 
 * The web interface lets you:
 *   - Load puzzles via text input or sample buttons
 *   - Solve using Naive Backtracking (Task 1), Smart Backtracking (Task 2), or both
 *   - View animated solutions and side-by-side performance metrics (Task 3)
 * 
 * CSP Formulation:
 *   Variables:   81 cells (cell[0][0] through cell[8][8])
 *   Domains:     {1, 2, 3, 4, 5, 6, 7, 8, 9} per empty cell
 *   Constraints: 27 AllDiff (9 rows + 9 columns + 9 boxes)
 * 
 * All solver logic runs client-side in JavaScript. The PHP wrapper is just
 * here so I can host it on my web server. You could rename this to .html
 * and it would work the same way. The PHP tag is basically just vibes.
 * ============================================================================
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CS480 Sudoku CSP Solver</title>
    <!-- Google Fonts: because Times New Roman is for term papers, not Sudoku solvers -->
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ============================================================
         * CSS VARIABLES
         * Setting up our color palette. Dark theme because we're
         * solving Sudoku at 2 AM like civilized AI students.
         * ============================================================ */
        :root {
            --bg: #0a0c10;
            --surface: #12151c;
            --surface2: #1a1e28;
            --border: #2a2f3d;
            --border-thick: #3d4459;
            --text: #e4e7ef;
            --text-dim: #8891a5;
            --accent: #6c9cff;           /* A calming blue for when the Evil puzzle makes you cry */
            --accent-glow: rgba(108, 156, 255, 0.15);
            --green: #5ce0a0;            /* The color of success and solved puzzles */
            --green-glow: rgba(92, 224, 160, 0.15);
            --orange: #ffad5c;           /* Warning: naive solver is working overtime */
            --orange-glow: rgba(255, 173, 92, 0.15);
            --red: #ff6b7a;              /* The color of "no solution found" and broken dreams */
            --purple: #b88cff;
            --cell-size: 46px;
            --mono: 'JetBrains Mono', monospace;
            --sans: 'DM Sans', sans-serif;
        }

        /* Universal reset. Every element starts with a clean slate,
           unlike my GPA after that one semester */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: var(--sans);
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        /* Subtle background grid pattern, because we're solving a GRID puzzle,
           get it? I'll see myself out. */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(108,156,255,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(108,156,255,0.03) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.5rem;
            position: relative;
            z-index: 1;
        }

        /* Header styling because first impressions matter,
           ESPECIALLY for a Sudoku solver */
        header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid var(--border);
        }
        header .badge {
            display: inline-block;
            font-family: var(--mono);
            font-size: 0.65rem;
            font-weight: 600;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--accent);
            background: var(--accent-glow);
            padding: 0.3rem 0.8rem;
            border-radius: 100px;
            border: 1px solid rgba(108,156,255,0.2);
            margin-bottom: 0.75rem;
        }
        header h1 {
            font-family: var(--mono);
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 0.35rem;
        }
        header h1 span { color: var(--accent); }
        header p { color: var(--text-dim); font-size: 0.9rem; }

        /* Two-column layout: board on the left, controls on the right. */
        .main-grid {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 1.75rem;
            align-items: start;
        }
        @media (max-width: 920px) {
            /* On mobile, stack everything. Sudoku on a phone is chaos anyway. */
            .main-grid { grid-template-columns: 1fr; }
        }

        /* ============================================================
         * SUDOKU BOARD STYLES
         * Making a 9x9 grid look good is harder than solving it.
         * ============================================================ */
        .board-section { text-align: center; }
        .board-wrapper {
            display: inline-block;
            background: var(--surface);
            border: 2px solid var(--border-thick);
            border-radius: 10px;
            padding: 10px;
        }
        .sudoku-grid {
            display: grid;
            grid-template-columns: repeat(9, var(--cell-size));
            grid-template-rows: repeat(9, var(--cell-size));
        }
        .sudoku-cell {
            width: var(--cell-size);
            height: var(--cell-size);
            border: 1px solid var(--border);
            background: var(--surface2);
            color: var(--text);
            font-family: var(--mono);
            font-size: 1.1rem;
            font-weight: 600;
            text-align: center;
            outline: none;
            transition: all 0.12s ease;
            caret-color: var(--accent);
        }
        .sudoku-cell:focus {
            background: rgba(108,156,255,0.08);
            border-color: var(--accent);
            z-index: 2;
            position: relative;
        }
        /* Pre-filled cells get a dimmer color so you know
           "these aren't my fault if the puzzle is wrong" */
        .sudoku-cell.given {
            color: var(--text-dim);
            background: rgba(255,255,255,0.02);
        }
        /* Solved cells pop in with a satisfying green animation.
           Dopamine delivery system, basically. */
        .sudoku-cell.solved {
            color: var(--green);
            animation: cellPop 0.25s ease forwards;
        }
        @keyframes cellPop {
            from { opacity: 0; transform: scale(0.7); }
            to { opacity: 1; transform: scale(1); }
        }
        /* Thick borders to separate the 3x3 boxes.
           Without these, it's just a spreadsheet with mommy issues. */
        .sudoku-cell.br { border-right: 2px solid var(--border-thick); }
        .sudoku-cell.bb { border-bottom: 2px solid var(--border-thick); }
        .sudoku-cell.bt { border-top: 2px solid var(--border-thick); }
        .sudoku-cell.bl { border-left: 2px solid var(--border-thick); }

        /* Button row. solve, clear, reset. The holy trinity. */
        .controls {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            justify-content: center;
            margin-top: 0.75rem;
        }
        .btn {
            font-family: var(--mono);
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.5rem 0.85rem;
            border: 1px solid var(--border);
            border-radius: 7px;
            background: var(--surface2);
            color: var(--text);
            cursor: pointer;
            transition: all 0.12s ease;
            letter-spacing: 0.02em;
        }
        .btn:hover { border-color: var(--accent); background: var(--accent-glow); }
        .btn.primary { background: var(--accent); color: var(--bg); border-color: var(--accent); }
        .btn.primary:hover { filter: brightness(1.12); }
        .btn:disabled { opacity: 0.35; cursor: not-allowed; }

        /* Status message tells you what's happening so you don't just stare */
        .status {
            font-family: var(--mono);
            font-size: 0.72rem;
            color: var(--text-dim);
            text-align: center;
            margin-top: 0.6rem;
            min-height: 1.2em;
        }
        .status.success { color: var(--green); }
        .status.error { color: var(--red); }

        /* Right-side panel cards */
        .panel { display: flex; flex-direction: column; gap: 1rem; }
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1.1rem;
        }
        .card h3 {
            font-family: var(--mono);
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--text-dim);
            margin-bottom: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }
        .card h3 .dot { width: 7px; height: 7px; border-radius: 50%; }
        .dot.blue { background: var(--accent); }
        .dot.green { background: var(--green); }
        .dot.orange { background: var(--orange); }
        .dot.purple { background: var(--purple); }

        /* Textarea for pasting puzzles or number jai (as i like to call it)l */
        .puzzle-input {
            width: 100%;
            min-height: 150px;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 7px;
            padding: 0.7rem;
            color: var(--text);
            font-family: var(--mono);
            font-size: 0.8rem;
            line-height: 1.55;
            resize: vertical;
            outline: none;
        }
        .puzzle-input:focus { border-color: var(--accent); }
        .puzzle-input::placeholder { color: var(--text-dim); opacity: 0.4; }

        /* Sample puzzle buttons because let's be honest. you're too lazy to type 81 digits */
        .samples { display: grid; grid-template-columns: 1fr 1fr; gap: 0.35rem; }
        .sample-btn {
            font-family: var(--mono);
            font-size: 0.68rem;
            padding: 0.45rem 0.6rem;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 5px;
            color: var(--text-dim);
            cursor: pointer;
            transition: all 0.12s;
            text-align: left;
        }
        .sample-btn:hover { border-color: var(--accent); color: var(--text); }
        .sample-btn .label { font-weight: 600; color: var(--text); display: block; margin-bottom: 0.1rem; }

        /* ============================================================
         * RESULTS & COMPARISON STYLES
         * This is where the naive solver gets publicly embarrassed.
         * ============================================================ */
        .results-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; }
        .result-col h4 {
            font-family: var(--mono);
            font-size: 0.68rem;
            font-weight: 700;
            padding: 0.4rem 0.65rem;
            border-radius: 5px;
            margin-bottom: 0.6rem;
        }
        .result-col.naive h4 { background: var(--orange-glow); color: var(--orange); border: 1px solid rgba(255,173,92,0.2); }
        .result-col.smart h4 { background: var(--green-glow); color: var(--green); border: 1px solid rgba(92,224,160,0.2); }
        .stat-row {
            display: flex;
            justify-content: space-between;
            padding: 0.32rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.03);
            font-size: 0.75rem;
        }
        .stat-row .stat-label { color: var(--text-dim); }
        .stat-row .stat-value { font-family: var(--mono); font-weight: 600; }
        .hl-green { color: var(--green) !important; }
        .hl-orange { color: var(--orange) !important; }

        /* Bar chart styles. Visual proof that heuristics matter */
        .bar-section { margin-top: 0.85rem; padding-top: 0.85rem; border-top: 1px solid var(--border); }
        .bar-section-title {
            font-family: var(--mono);
            font-size: 0.65rem;
            font-weight: 700;
            color: var(--text-dim);
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 0.7rem;
        }
        .bar-group { margin-bottom: 0.55rem; }
        .bar-group-label { font-family: var(--mono); font-size: 0.65rem; color: var(--text-dim); margin-bottom: 0.2rem; }
        .bar-row { display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.2rem; }
        .bar-lbl { font-family: var(--mono); font-weight: 600; font-size: 0.65rem; width: 55px; text-align: right; }
        .bar-track { flex: 1; height: 16px; background: var(--surface2); border-radius: 3px; overflow: hidden; }
        .bar-fill { height: 100%; border-radius: 3px; transition: width 0.5s ease; min-width: 2px; }
        .bar-fill.naive { background: var(--orange); }
        .bar-fill.smart { background: var(--green); }
        .bar-val { font-family: var(--mono); font-size: 0.65rem; font-weight: 600; width: 75px; color: var(--text-dim); }

        /* The improvement badge. the Smart solver's victory lap */
        .imp-badge {
            display: inline-block;
            font-family: var(--mono);
            font-size: 0.65rem;
            font-weight: 700;
            padding: 0.25rem 0.55rem;
            border-radius: 100px;
            background: var(--green-glow);
            color: var(--green);
            border: 1px solid rgba(92,224,160,0.2);
            margin-top: 0.6rem;
        }

        /* Loading spinner. It spins. And looks cool af. */
        .spinner {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 2px solid rgba(255,255,255,0.15);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin 0.55s linear infinite;
            vertical-align: middle;
            margin-right: 0.35rem;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        footer {
            text-align: center;
            padding: 1.5rem 0 1rem;
            color: var(--text-dim);
            font-size: 0.75rem;
            border-top: 1px solid var(--border);
            margin-top: 1.75rem;
        }
        footer a { color: var(--accent); text-decoration: none; }
    </style>
</head>
<body>
<div class="container">
    <header>
        <div class="badge">CS480 Module 4 — Constraint Satisfaction Problems</div>
        <h1>Sudoku <span>CSP</span> Solver</h1>
        <p>Naive vs Smart Backtracking (MRV, LCV, Forward Checking)</p>
    </header>

    <div class="main-grid">
        <!-- Left side: the actual Sudoku board. The star of the show. -->
        <div class="board-section">
            <div class="board-wrapper">
                <div class="sudoku-grid" id="grid"></div>
            </div>
            <div class="controls">
                <button class="btn primary" onclick="solve('both')">Solve Both</button>
                <button class="btn" onclick="solve('naive')">Naive Only</button>
                <button class="btn" onclick="solve('smart')">Smart Only</button>
                <button class="btn" onclick="clearBoard()">Clear</button>
                <button class="btn" onclick="resetBoard()">Reset</button>
            </div>
            <div class="status" id="status"></div>
        </div>

        <!-- Right side: the control panel. Mission control for number puzzles. -->
        <div class="panel">
            <div class="card">
                <h3><span class="dot blue"></span> Load Puzzle</h3>
                <textarea class="puzzle-input" id="puzzleInput" placeholder="Paste 9 lines of 9 digits (0=empty)&#10;003020600&#10;900305001&#10;..."></textarea>
                <div style="margin-top:0.5rem;">
                    <button class="btn primary" onclick="loadFromText()">Load</button>
                </div>
            </div>

            <div class="card">
                <h3><span class="dot orange"></span> Sample Puzzles</h3>
                <div class="samples" id="samples"></div>
            </div>

            <div class="card" id="resultsCard" style="display:none">
                <h3><span class="dot green"></span> Performance Comparison</h3>
                <div id="resultsContent"></div>
            </div>
        </div>
    </div>

    <footer>
        CS480 Introduction to Artificial Intelligence / Old Dominion University<br>
        <a href="https://donnelgarner.com/projects/CS480/sudoku-solver/" target="_blank">Sudoku Solver</a> by Donnel Garner
    </footer>
</div>

<script>
// ============================================================================
// CS480 SUDOKU CSP SOLVER — JavaScript Engine
// Author:  Donnel Garner
// Date:    February 18, 2026
// Course:  CS480 - Introduction to Artificial Intelligence
//
// DESCRIPTION:
// Solves Sudoku puzzles as Constraint Satisfaction Problems using:
//   Task 1: Naive Backtracking — the "try everything and hope" approach
//   Task 2: Smart Backtracking — MRV + LCV + Forward Checking (the big brain approach)
// Then compares their performance so we can see how much smarter beats harder.
// ============================================================================


// ============================================================
// GLOBAL STATE
// The 'given' array stores the original puzzle. Think of it as
// the save point you reload when the boss fight goes wrong.
// ============================================================
let given = Array.from({length:9}, ()=>Array(9).fill(0));

// Sample puzzles at different difficulty levels.
const PUZZLES = {
    'Easy': '003020600\n900305001\n001806400\n008102900\n700000008\n006708200\n002609500\n800203009\n005010300',
    'Medium': '200080300\n060070084\n030500209\n000105408\n000000000\n402706000\n301007040\n720040060\n004010003',
    'Hard': '000000680\n030000000\n069000030\n004800500\n010070020\n005002100\n070000950\n000000070\n081000000',
    'Evil': '800000000\n003600000\n070090200\n050007000\n000045700\n000100030\n001000068\n008500010\n090000400',
    'Assign #1': '003020600\n900305001\n001806400\n008102900\n700000008\n006708200\n002609500\n800203009\n005010300',
    'Assign #2': '000008390\n400300005\n000020081\n800000054\n007000900\n130000008\n340090000\n900006007\n081200000',
};


// ============================================================
// BOARD UI FUNCTIONS
// All the code that makes the grid look pretty and interactive.
// The real MVP is CSS Grid.
// ============================================================

/**
 * Build the 9x9 grid of input cells.
 * 81 tiny input boxes.
 */
function initGrid() {
    const g = document.getElementById('grid');
    g.innerHTML = '';
    for (let r=0; r<9; r++) {
        for (let c=0; c<9; c++) {
            const inp = document.createElement('input');
            inp.type = 'text'; inp.maxLength = 1;
            inp.className = 'sudoku-cell';
            inp.id = `c${r}${c}`;

            // Add thick borders for 3x3 box separation.
            // Without these borders, Sudoku is just a sad spreadsheet.
            if (c % 3 === 0) inp.classList.add('bl');
            if (c === 8 || c % 3 === 2) inp.classList.add('br');
            if (r % 3 === 0) inp.classList.add('bt');
            if (r === 8 || r % 3 === 2) inp.classList.add('bb');

            // Only allow digits 1-9.
            inp.addEventListener('input', e => {
                e.target.value = e.target.value.replace(/[^1-9]/g,'');
            });

            // Arrow key navigation so you can move around the grid
            inp.addEventListener('keydown', e => navKey(e, r, c));
            g.appendChild(inp);
        }
    }
}

/**
 * Arrow key navigation handler.
 * Because clicking 81 individual cells would be a human rights violation.
 */
function navKey(e, r, c) {
    let nr=r, nc=c;
    switch(e.key) {
        case 'ArrowUp': nr=Math.max(0,r-1); break;
        case 'ArrowDown': nr=Math.min(8,r+1); break;
        case 'ArrowLeft': nc=Math.max(0,c-1); break;
        case 'ArrowRight': nc=Math.min(8,c+1); break;
        default: return; // Not our problem, let the browser handle it
    }
    e.preventDefault();
    document.getElementById(`c${nr}${nc}`).focus();
}

/**
 * Render the given (original) puzzle onto the board.
 * Pre-filled cells become read-only. look, but don't touch.
 */
function renderGiven() {
    for (let r=0;r<9;r++) for (let c=0;c<9;c++) {
        const el = document.getElementById(`c${r}${c}`);
        const v = given[r][c];
        el.value = v > 0 ? v : '';
        el.className = el.className.replace(/ ?(given|solved)/g,'');
        if (v > 0) { el.classList.add('given'); el.readOnly = true; }
        else { el.readOnly = false; }
    }
}

/**
 * Animate the solution onto the board cell by cell.
 * Each solved cell pops in with a satisfying green animation.
 */
function showSolution(board) {
    for (let r=0;r<9;r++) for (let c=0;c<9;c++) {
        const el = document.getElementById(`c${r}${c}`);
        el.value = board[r][c];
        if (given[r][c] === 0 && board[r][c] > 0) {
            // Stagger the animations so it looks cool instead of instant.
            // 12ms per cell = about 1 second for the full board reveal.
            setTimeout(()=> el.classList.add('solved'), (r*9+c)*12);
        }
    }
}

/** Nuke the board. Gone. Reduced to atoms. */
function clearBoard() {
    given = Array.from({length:9},()=>Array(9).fill(0));
    renderGiven();
    document.getElementById('resultsCard').style.display='none';
    setStatus('');
}

/** Reset to the original puzzle. */
function resetBoard() { renderGiven(); setStatus(''); document.getElementById('resultsCard').style.display='none'; }

/** Update the status message below the board. */
function setStatus(msg, cls='') {
    const el = document.getElementById('status');
    el.innerHTML = msg; el.className = 'status' + (cls ? ' '+cls : '');
}

/**
 * Parse puzzle text from the textarea and load it onto the board.
 * Expects 9 lines of 9 digits. Anything else gets the red text of shame.
 */
function loadFromText() {
    const txt = document.getElementById('puzzleInput').value.trim();
    if (!txt) return;
    const lines = txt.split(/\r?\n/).map(l=>l.trim()).filter(l=>/^[0-9]{9}$/.test(l));
    if (lines.length !== 9) { setStatus('Need 9 lines of 9 digits','error'); return; }
    for (let r=0;r<9;r++) for (let c=0;c<9;c++) given[r][c] = +lines[r][c];
    renderGiven();
    setStatus('Puzzle loaded','success');
    document.getElementById('resultsCard').style.display='none';
}

/** Load a sample puzzle. */
function loadSample(key) {
    document.getElementById('puzzleInput').value = PUZZLES[key];
    loadFromText();
}

/** Build the sample puzzle buttons. Six flavors of number pain. */
function initSamples() {
    const el = document.getElementById('samples');
    el.innerHTML = Object.keys(PUZZLES).map(k =>
        `<button class="sample-btn" onclick="loadSample('${k}')"><span class="label">${k}</span></button>`
    ).join('');
}


// ============================================================================
// ██████████████████████████████████████████████████████████████████████████████
// TASK 1: NAIVE BACKTRACKING SOLVER
// ██████████████████████████████████████████████████████████████████████████████
//
// How it works:
//   SELECT-UNASSIGNED-VARIABLE: Picks cells left-to-right, top-to-bottom.
//       Like reading a book. cell by cell.
//
//   ORDER-DOMAIN-VALUES: Tries 1, then 2, then 3, ..., then 9. Every time.
//       It doesn't care which value is most likely to work.
//
//   INFERENCE: None. Zero. Zilch. It just yeets a number in and hopes.
//       If it doesn't work, it backs up and tries the next one. Brute force
//       at its finest. Like trying every key on a keyring.
//
// CSP Details:
//   Variables:   81 cells (the empty ones are our unknowns)
//   Domains:     {1, 2, 3, 4, 5, 6, 7, 8, 9} for each empty cell
//   Constraints: 27 AllDiff constraints (9 rows + 9 columns + 9 boxes)
//       Meaning: no duplicate numbers in any row, column, or 3x3 box.
//
// This works fine for Easy puzzles. For Evil puzzles... bring a snack.
// ============================================================================
function solveNaive(boardInput) {
    // Deep copy the board so we don't accidentally solve the original.
    const board = boardInput.map(r=>[...r]);

    // Find all empty cells. These are our CSP variables.
    // A "variable" in CSP terms is just a cell that needs a value.
    const empty = [];
    for (let r=0;r<9;r++) for (let c=0;c<9;c++) if (board[r][c]===0) empty.push([r,c]);

    // Performance counters — keeping score so we can roast this
    // algorithm later when we compare it to the smart one.
    let backtracks = 0,  // How many times we said "nope, wrong answer"
        checks = 0,      // How many times we checked a constraint
        assigns = 0;     // How many times we placed a number

    /**
     * isConsistent: The constraint police.
     *
     * Checks if placing 'num' at position (r,c) would violate any of
     * our 27 AllDiff constraints. Specifically:
     *   1. Row check: is this number already in the same row?
     *   2. Column check:  is this number already in the same column?
     *   3. Box check: is this number already in the same 3x3 subgrid?
     *
     * If ANY of these checks fail, the assignment is inconsistent (illegal).
     */
    function isConsistent(r, c, num) {
        // Check the row: scanning all 9 columns in this row
        for (let i=0;i<9;i++) { checks++; if (board[r][i]===num) return false; }
        // Check the column: scanning all 9 rows in this column
        for (let i=0;i<9;i++) { checks++; if (board[i][c]===num) return false; }
        // Check the 3x3 box: the little squares within the big square
        const br=Math.floor(r/3)*3, bc=Math.floor(c/3)*3;
        for (let i=br;i<br+3;i++) for (let j=bc;j<bc+3;j++) { checks++; if (board[i][j]===num) return false; }
        return true; // All clear
    }

    /**
     * backtrack: The main recursive search function.
     *
     * This is the BACKTRACKING-SEARCH algorithm from Figure 6.5 in the textbook.
     * It works like this:
     *   1. If we've filled all empty cells → we win! Return true.
     *   2. Grab the next empty cell (in reading order).
     *   3. Try every number 1-9:
     *      a. If the number doesn't violate any constraints, place it.
     *      b. Recursively try to solve the rest of the puzzle.
     *      c. If it works → great, pass the success back up.
     *      d. If it doesn't → erase the number (backtrack) and try the next one.
     *   4. If no number works → this branch is a dead end. Back up.
     *
     * It's like navigating a maze by always going right.
     * Eventually you'll find the exit... eventually.
     */
    function backtrack(idx) {
        // Base case: all variables assigned. We did it! Pack it up, go home.
        if (idx >= empty.length) return true;

        // SELECT-UNASSIGNED-VARIABLE: just grab the next one in order.
        // No thinking involved. This is the "naive" part.
        const [r,c] = empty[idx];

        // ORDER-DOMAIN-VALUES: try 1, 2, 3, 4, 5, 6, 7, 8, 9.
        // Always in that order.
        for (let num=1; num<=9; num++) {
            if (isConsistent(r, c, num)) {
                board[r][c] = num;   // Place the number (assignment)
                assigns++;

                if (backtrack(idx+1)) return true;  // Recurse to next variable

                board[r][c] = 0;     // Undo. Classic backtracking.
                backtracks++;        // Add another tally to the wall of shame.
            }
        }
        return false;  // None of 1-9 worked. Time to retreat. Tactical withdrawal.
    }

    // Let's time this bad girl
    const t0 = performance.now();
    const solved = backtrack(0);
    const t1 = performance.now();

    return {
        solved, board,
        time: +(t1-t0).toFixed(3),
        backtracks, checks, assigns,
        empties: empty.length,
        algo: 'Naive Backtracking'
    };
}


// ============================================================================
// ██████████████████████████████████████████████████████████████████████████████
// TASK 2: SMART BACKTRACKING (MRV + LCV + Forward Checking)
// ██████████████████████████████████████████████████████████████████████████████
//
// Three upgrades over the naive solver:
//
// 1. MRV: Minimum Remaining Values (Section 6.3.1)
//    "Fail-first" variable selection. Instead of just going left-to-right,
//    we pick the cell with the FEWEST legal values remaining. Why?
//    If a cell only has 1 option, assign it now. If it has 0 options,
//    we know immediately this path is doomed. No need to keep digging.
//    It's like doing the hardest exam question first so you know early
//    if you need to drop the class.
//
// 2. LCV: Least Constraining Value (Section 6.3.1)
//    When choosing which number to try, pick the one that rules out the
//    FEWEST options for neighboring cells. This keeps the most doors open
//    for future assignments. It's the "don't burn bridges" strategy.
//    For variable ordering we fail-first, but for value ordering
//    we fail-LAST. The textbook literally says this.
//
// 3. Forward Checking: FC (Section 6.3.2)
//    After placing a number, immediately go tell all the neighbors
//    "hey, you can't use this number anymore" and shrink their domains.
//    If any neighbor's domain hits zero, we know RIGHT NOW that this
//    path is a dead end. Backtrack immediately instead of wasting time
//    exploring a doomed subtree.
//
// Together, these three heuristics absolutely smoke the naive approach.
// Same answer, way less work.
// ============================================================================
function solveSmart(boardInput) {
    // Deep copy. We don't want to mess up the original board.
    // Learned that lesson the hard way.
    const board = boardInput.map(r=>[...r]);

    // Performance counters. Same as naive, plus a pruning counter.
    let backtracks = 0,  // Times we had to undo an assignment
        checks = 0,      // Constraint checks performed
        assigns = 0,     // Total assignments made
        pruned = 0;      // Values eliminated by Forward Checking (the flex stat)

    // ---- DOMAIN INITIALIZATION ----
    // For each empty cell, compute the initial set of legal values.
    // This is basically doing node consistency upfront, removing any
    // value that's already in the cell's row, column, or box.
    // The domains shrink further as we make assignments (via Forward Checking).
    const domains = Array.from({length:9}, ()=>Array.from({length:9}, ()=>null));
    const empties = [];

    for (let r=0;r<9;r++) for (let c=0;c<9;c++) {
        if (board[r][c] === 0) {
            domains[r][c] = computeDomain(r, c);
            empties.push([r,c]);
        }
    }

    /**
     * computeDomain: Figure out what numbers are still legal for cell (r,c).
     * Scans the row, column, and 3x3 box to find what's already taken.
     * Whatever's left is the domain.
     */
    function computeDomain(r, c) {
        const used = new Set();
        for (let i=0;i<9;i++) { if (board[r][i]) used.add(board[r][i]); if (board[i][c]) used.add(board[i][c]); }
        const br=Math.floor(r/3)*3, bc=Math.floor(c/3)*3;
        for (let i=br;i<br+3;i++) for (let j=bc;j<bc+3;j++) if (board[i][j]) used.add(board[i][j]);
        const d = [];
        for (let v=1;v<=9;v++) if (!used.has(v)) d.push(v);
        return d;
    }

    /**
     * getNeighbors: Find all cells that share a constraint with (r,c).
     * That means every cell in the same row, same column, or same 3x3 box.
     * Each cell has exactly 20 unique neighbors.
     * These are the cells that care about what we put here.
     */
    function getNeighbors(r, c) {
        const nb = [], seen = new Set();
        for (let i=0;i<9;i++) {
            if (i!==c) { const k=r*9+i; if (!seen.has(k)){seen.add(k);nb.push([r,i]);} }
            if (i!==r) { const k=i*9+c; if (!seen.has(k)){seen.add(k);nb.push([i,c]);} }
        }
        // Same 3x3 box neighbors (might overlap with row/col, hence the Set)
        const br=Math.floor(r/3)*3, bc=Math.floor(c/3)*3;
        for (let i=br;i<br+3;i++) for (let j=bc;j<bc+3;j++) {
            if (i!==r||j!==c) { const k=i*9+j; if (!seen.has(k)){seen.add(k);nb.push([i,j]);} }
        }
        return nb;
    }

    /**
     * selectMRV: SELECT-UNASSIGNED-VARIABLE using Minimum Remaining Values.
     *
     * Scans ALL unassigned cells and picks the one with the smallest domain.
     * If two cells are tied, we use the DEGREE HEURISTIC as a tie-breaker:
     * pick the cell with the most unassigned neighbors (more constraints =
     * more likely to cause problems later, so deal with it now).
     *
     * This is the "fail-first" strategy from Section 6.3.1:
     * - Cell with 1 value left? Assign it now, no brainer.
     * - Cell with 0 values left? Dead end detected, backtrack immediately.
     * - Cell with 2 values? Better to try this than a cell with 7 options.
     *
     * Returns null when all cells are assigned (puzzle solved!).
     *
     * Fun fact: the textbook says MRV can improve performance by a factor
     * of 1,000 or more. That's not a typo. A THOUSAND. Read Chapter 6, people.
     */
    function selectMRV() {
        let best=null, bestSize=10, bestDeg=-1;
        for (let r=0;r<9;r++) for (let c=0;c<9;c++) {
            if (board[r][c]===0) {
                const sz = domains[r][c].length;
                if (sz < bestSize) {
                    bestSize=sz; best=[r,c]; bestDeg=getDegree(r,c);
                } else if (sz===bestSize) {
                    // Tie!
                    const deg=getDegree(r,c);
                    if (deg>bestDeg) { best=[r,c]; bestDeg=deg; }
                }
            }
        }
        return best;
    }

    /**
     * getDegree: Count how many unassigned neighbors this cell has.
     * Used as a tie-breaker for MRV.
     */
    function getDegree(r,c) {
        let d=0;
        for (const [nr,nc] of getNeighbors(r,c)) if (board[nr][nc]===0) d++;
        return d;
    }

    /**
     * orderLCV: ORDER-DOMAIN-VALUES using Least Constraining Value.
     *
     * For each possible value in this cell's domain, count how many
     * neighbor domain values it would eliminate. Sort ascending.
     * Try the LEAST constraining value first. Leave maximum flexibility for future assignments.
     *
     *
     * Variable selection = fail-FIRST (MRV). Value selection = fail-LAST (LCV).
     * Yes, they're opposites. No, it's not a contradiction. It's strategy.
     */
    function orderLCV(r, c) {
        const dom = domains[r][c];
        if (dom.length<=1) return [...dom]; // Only one choice? No need to sort a list of one.
        const nbs = getNeighbors(r,c);
        const costs = dom.map(v => {
            let cost=0;
            for (const [nr,nc] of nbs) {
                if (board[nr][nc]===0) {
                    checks++;
                    // If this value appears in a neighbor's domain, placing it here
                    // would shrink that neighbor's options. That's a "cost."
                    if (domains[nr][nc].includes(v)) cost++;
                }
            }
            return {v, cost};
        });
        costs.sort((a,b)=>a.cost-b.cost); // Lowest cost first. Least constraining wins
        return costs.map(x=>x.v);
    }

    /**
     * forwardCheck: INFERENCE via Forward Checking (Section 6.3.2).
     *
     * After we assign 'val' to cell (r,c), we go to every unassigned neighbor
     * and remove 'val' from their domain. Makes sense if we put a 7 here,
     * nobody else in this row/column/box can also be 7.
     *
     * The magic: if ANY neighbor's domain becomes EMPTY after pruning,
     * we IMMEDIATELY know this assignment is a dead end. No need to keep
     * searching. Just undo everything and try a different value.
     *
     * This is way smarter than the naive approach, which would keep going
     * until it hits a wall 20 recursive calls later.
     *
     * Returns the list of pruned values (for undo on backtrack), or null if
     * a domain wipeout was detected (meaning: abandon ship).
     */
    function forwardCheck(r, c, val) {
        const prunedList = []; // Keep track of what we removed so we can undo it
        for (const [nr,nc] of getNeighbors(r,c)) {
            if (board[nr][nc]===0) {
                checks++;
                const idx = domains[nr][nc].indexOf(val);
                if (idx !== -1) {
                    // Remove this value from the neighbor's domain
                    domains[nr][nc].splice(idx, 1);
                    prunedList.push([nr, nc, val]);
                    pruned++; // Another value bites the dust

                    // DOMAIN WIPEOUT CHECK: if a neighbor has NO legal values left,
                    // this assignment is doomed. Abort! Abort!
                    if (domains[nr][nc].length === 0) {
                        undoPrune(prunedList); // Clean up our mess before leaving
                        return null;           // Signal failure to the caller
                    }
                }
            }
        }
        return prunedList; // Everything's fine (for now)
    }

    /**
     * undoPrune: Restore domain values that were removed by Forward Checking.
     * When we backtrack, we need to put everything back the way we found it.
     */
    function undoPrune(list) {
        for (const [r,c,v] of list) domains[r][c].push(v);
    }

    /**
     * backtrack: The main search function: Smart Edition.
     *
     * Same structure as BACKTRACKING-SEARCH in Figure 6.5, but upgraded:
     *   - MRV picks the variable     (which cell to fill next)
     *   - LCV orders the values      (which number to try first)
     *   - FC does inference           (prune neighbors after each assignment)
     *
     * The result: dramatically fewer backtracks, fewer constraint checks,
     * and way less time wasted exploring dead-end branches.
     *
     * It's still backtracking at its core.
     */
    function backtrack() {
        // MRV: pick the most constrained unassigned cell
        const cell = selectMRV();
        if (!cell) return true; // No unassigned cells left
        const [r,c] = cell;

        // LCV: order values by least constraining first
        const values = orderLCV(r, c);

        for (const val of values) {
            // Make the assignment
            board[r][c] = val; assigns++;
            const oldDom = [...domains[r][c]]; // Save domain for potential undo
            domains[r][c] = [val];

            // Forward Checking: propagate constraints to neighbors
            const fc = forwardCheck(r, c, val);
            if (fc !== null) {
                // FC didn't detect a dead end. Keep going deeper
                if (backtrack()) return true;
                // Recursion failed. Undo the forward checking pruning
                undoPrune(fc);
            }
            // If fc was null, a domain wipeout occurred. Skip straight to backtrack

            // Backtrack: undo the assignment and restore the domain
            board[r][c] = 0;
            domains[r][c] = oldDom;
            backtracks++;
        }
        return false; // Tried everything, nothing worked. We're cooked.
    }

    // Clock it!
    const t0 = performance.now();
    const solved = backtrack();
    const t1 = performance.now();

    return {
        solved, board,
        time: +(t1-t0).toFixed(3),
        backtracks, checks, assigns, pruned,
        empties: empties.length,
        algo: 'Smart (MRV+LCV+FC)'
    };
}


// ============================================================================
// SOLVE & DISPLAY FUNCTIONS
// The glue code that connects the solvers to the UI.
// Click button → run solver → show results. Simple as that.
// ============================================================================

/**
 * Main solve function. Runs one or both solvers and displays results.
 * Uses setTimeout so the "Solving..." spinner actually shows up
 * before the CPU goes full send on the recursive backtracking.
 */
function solve(mode='both') {
    const hasData = given.some(r=>r.some(v=>v>0));
    if (!hasData) { setStatus('Load a puzzle first','error'); return; }

    setStatus('<span class="spinner"></span> Solving...','');
    document.querySelectorAll('.btn').forEach(b=>b.disabled=true);

    // setTimeout: give the browser a moment to render the spinner
    // before we lock up the main thread with recursive math
    setTimeout(()=>{
        let naiveR=null, smartR=null;
        if (mode==='naive'||mode==='both') naiveR = solveNaive(given);
        if (mode==='smart'||mode==='both') smartR = solveSmart(given);

        const best = smartR || naiveR;
        if (best && best.solved) { showSolution(best.board); setStatus('Solved!','success'); }
        else setStatus('No solution found','error');

        showResults(naiveR, smartR);
        document.querySelectorAll('.btn').forEach(b=>b.disabled=false);
    }, 30);
}

/** Build the results comparison panel. This is where the naive solver cries. */
function showResults(naive, smart) {
    const card = document.getElementById('resultsCard');
    const el = document.getElementById('resultsContent');
    card.style.display = 'block';

    let html = '<div class="results-grid">';
    if (naive) html += resultCol(naive, 'naive');
    if (smart) html += resultCol(smart, 'smart');
    html += '</div>';

    if (naive && smart) html += barComp(naive, smart);
    el.innerHTML = html;
}

/** Generate a results column for one solver. Stats on stats on stats. */
function resultCol(d, type) {
    const hl = type==='naive'?'hl-orange':'hl-green';
    const title = type==='naive'?'Naive Backtracking':'Smart (MRV+LCV+FC)';
    let h = `<div class="result-col ${type}"><h4>${title}</h4>`;
    h += stat('Status', d.solved?'Solved':'Failed', d.solved?'hl-green':'');
    h += stat('Time', d.time+' ms', hl);
    h += stat('Backtracks', d.backtracks.toLocaleString(), hl);
    h += stat('Assignments', d.assigns.toLocaleString(), hl);
    h += stat('Constraint Checks', d.checks.toLocaleString(), hl);
    if (d.pruned!==undefined) h += stat('Pruned by FC', d.pruned.toLocaleString(), hl);
    h += stat('Empty Cells', d.empties, '');
    return h + '</div>';
}

/** Helper to build a single stat row. Label on the left, number on the right. */
function stat(label, val, cls) {
    return `<div class="stat-row"><span class="stat-label">${label}</span><span class="stat-value ${cls}">${val}</span></div>`;
}

/**
 * Build the visual bar chart comparison.
 * Orange bars = naive. Green bars = smart.
 * The bigger the gap, the more satisfying it is.
 */
function barComp(n, s) {
    const metrics = [
        ['Time (ms)', n.time, s.time],
        ['Backtracks', n.backtracks, s.backtracks],
        ['Assignments', n.assigns, s.assigns],
        ['Constraint Checks', n.checks, s.checks],
    ];
    let h = '<div class="bar-section"><div class="bar-section-title">Visual Comparison</div>';
    for (const [label, nv, sv] of metrics) {
        const mx = Math.max(nv, sv, 0.001); // Avoid division by zero
        h += `<div class="bar-group"><div class="bar-group-label">${label}</div>`;
        h += bar('Naive', 'naive', 'var(--orange)', nv, mx);
        h += bar('Smart', 'smart', 'var(--green)', sv, mx);
        h += '</div>';
    }
    // Show the improvement percentage. The smart solver's victory lap
    if (n.backtracks > 0) {
        const imp = ((1 - s.backtracks/n.backtracks)*100).toFixed(1);
        h += `<div class="imp-badge">${imp}% fewer backtracks with Smart solver</div>`;
    }
    return h + '</div>';
}

/** Build a single comparison bar. Simple, colorful, devastating to the naive solver. */
function bar(label, cls, color, val, max) {
    const pct = (val/max*100).toFixed(1);
    return `<div class="bar-row"><span class="bar-lbl" style="color:${color}">${label}</span>` +
        `<div class="bar-track"><div class="bar-fill ${cls}" style="width:${pct}%"></div></div>` +
        `<span class="bar-val">${Number(val).toLocaleString()}</span></div>`;
}


// ============================================================
// INITIALIZATION
// Everything kicks off here when the page loads.
// Build the grid, set up the samples, and load a puzzle
// ============================================================
document.addEventListener('DOMContentLoaded', ()=>{
    initGrid();       // Build the 81-cell grid
    initSamples();    // Create the sample puzzle buttons
    loadSample('Assign #2'); // Pre-load Assignment #2 because we're here to work, not stare at blank cells
});
</script>
</body>
</html>