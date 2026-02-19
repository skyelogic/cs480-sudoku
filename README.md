# Sudoku Solver (CSPs)

---

## 👨‍💻 Author
**Donnel Garner**  
Old Dominion University  
Norfolk, Virginia  

**CS480 – Introduction to AI**  
**Spring 2026** 

📅 **Due Date:** February 16, 2026  
🔗 **GitHub Repository:** [spr26-skyelogic](https://github.com/skyelogic/cs480-sudoku/)  
🌐 **Sudoku Solver:** [Sudoku Solver](https://donnelgarner.com/projects/CS480/sudoku-solver/)

---

## 📋 Table of Contents
- [How to Run](#-how-to-run)
- [Task 1: Naive Backtracking](#-task-1-naive-backtracking)
- [Task 2: Smart Backtracking](#-task-2-smart-backtracking)
- [Task 3: Report and Analysis](#%EF%B8%8F-task-3-report-and-analysis)
- [Technologies Used](#%EF%B8%8F-technologies-used)
- [References](#-references)

---

## 🚀 How to Run

### Option 1: PHP Built-in Server
```bash
# cd sudoku/
# php -S localhost:8080
Open http://localhost:8080 in your browser.
```

### Option 2: Open Directly (No Server Needed)
```bash
Rename index.php to index.html and open it directly in a browser. All solver logic runs client-side in JavaScript.
```

### Option 3: Deploy to Web Server
```bash
Upload index.php to your web server document root and navigate to it.
```
Or, simply follow this [link](https://donnelgarner.com/projects/CS480/sudoku-solver/)

## 📊 Task 1: Naive Backtracking

- Variable selection: Sequential (left-to-right, top-to-bottom)
- Value ordering: Sequential (1, 2, 3, ..., 9)
- Inference: None

---

## 🌐 Task 2: Smart Backtracking

- MRV (Minimum Remaining Values): Selects the cell with the fewest legal values , "fail-first" heuristic that prunes dead ends earlier
- LCV (Least Constraining Value): Tries values that eliminate the fewest options from neighbors first, maximizes future flexibility
- Forward Checking: After each assignment, removes the value from all neighbor domains; if any domain empties, backtracks immediately

---

## 🕷️ Task 3: Report and Analysis

### Metrics Collected

| Metric              | Description                                              |
|---------------------|----------------------------------------------------------|
| Time (ms)           | Wall-clock solve time                                    |
| Backtracks          | Number of assignment undos                               |
| Assignments         | Total variable assignments attempted                     |
| Constraint Checks   | Individual constraint evaluations performed               |
| Pruned by FC        | Domain values eliminated by Forward Checking (Smart only)|

### Challenges Encountered

| Challenge | Description | Resolution |
|-----------|-------------|------------|
| No PHP runtime in dev environment | The initial plan was to build the solvers as server-side PHP classes, but PHP wasn't available in the development environment for testing | Pivoted to a fully client-side JavaScript implementation wrapped in a `.php` file, making it both testable and deployable on a PHP web server |
| Evil puzzles and naive solver performance | The naive backtracking solver takes noticeably longer on Hard and Evil difficulty puzzles due to the massive number of backtracks and constraint checks | This actually became a feature. It perfectly demonstrates *why* MRV, LCV, and Forward Checking matter, which is the whole point of Task 3 |
| UI blocking during solve | JavaScript is single-threaded, so running a recursive solver locks up the browser and prevents the loading spinner from rendering | Used `setTimeout()` with a 30ms delay before solving to give the browser a frame to paint the spinner before computation begins |
| Forward Checking undo complexity | When FC detects a domain wipeout, all pruned values need to be restored in the correct order before backtracking, or domains get corrupted | Maintained a `prunedList` array that tracks every `[row, col, value]` removal, enabling clean rollback on failure |
| Domain integrity across recursion | The smart solver modifies shared domain arrays during forward checking, which can cause subtle bugs if references aren't handled carefully | Used spread operator (`[...array]`) for deep copies of domains before modification and restored them explicitly on backtrack |

### Results

📊 **Smart Backtracking is obviously more powerful, thus using the power of the dark side**  
⏱️ **Runtime:** Approximately 80% more efficient using Smart solver
💾 **Date Information:** If I had more time, then I'd look into an average of data. But I'm busy.

---

## 🛠️ Technologies Used

### Languages
- PHP - Just the wrapper tag so it can be served from my web server
- JavaScript - All the solver logic, UI interactivity, and performance measurement runs client-side in vanilla JS. No frameworks. No libraries!!!!
- HTML5 - Page structure, semantic elements, input cells for the grid
- CSS3 - All styling, including CSS Grid for board layout, CSS for theming, keyframe animations, and media queries

### External Resources
- Google Fonts - Jetbrains Mono and DM Sans
- SSH/Terminal (Putty / VSCODE)
- HTTP protocol understanding
- Bandicam (screenshots)

### Browser APIs Used
- **.performance.now()** - high-resolution timer for measuring solver execution time in MS
- **setTimeout()** - allows the UI to render the loading spinner before CPU-heavy solver runs
- **DOM manipulation** - createElement, getElementById, classList, event listeners for grid

---

## 📚 References

Through this assignment, I gained hands-on experience with:

✅ **Russell & Norvig, Artificial Intelligence: A Modern Approach** - Required Textbook  
✅ **[Claude Code](https://code.claude.com/docs/en/overview)** - Working with headers, redirects, and content negotiation    
✅ **[My Github Sudoku Solver](https://github.com/skyelogic/cs480-sudoku/)** - Donnel Garner's Repository  
✅ **[Visual Studio](https://code.visualstudio.com/)** - VSCode IDE by Microsoft  
✅ **[Sudoku Solver](https://donnelgarner.com/projects/CS480/sudoku-solver/)** - Forward facing website of Sudoku Solver  
✅ **[Web Sudoku](http://www.websudoku.com/)** - Required web site for solving sudoku boards  
✅ **[Brave Browser AI Search](https://brave.com/ai/)** - Brave Browser AI Powered Search Bar  

---

## 📝 License

This project is submitted as coursework for CS 480/580 at Old Dominion University.  

---

## 🙏 Acknowledgments

Special thanks to:  
- **Dr. Vikas Ashok** - Course instructor  
- **Old Dominion University** - Computer Science program  

---

<p align="center">
  <strong>Made with ☕ and 💻 by Donnel Garner</strong><br>
  <sub>Old Dominion University | CS 480 | Spring 2026</sub>
</p>

---

<p align="center">
  <a href="https://donnelgarner.com">🌐 Personal Website</a> •
  <a href="https://github.com/skyelogic">💻 GitHub</a>
</p>

