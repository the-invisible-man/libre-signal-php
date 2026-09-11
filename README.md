# LibreSignal-PHP 🚦🐘

A PHP practice framework for CodeSignal's Industry Coding Framework (ICF) assessments.

This is a PHP port of [LibreSignal](https://github.com/EricZheng0404/LibreSignal) by Eric Zheng. The problem statements, reference solutions, and test cases are the same. Only the language, the tooling, and the method names (camelCase instead of snake_case) have changed.

## 🎯 Purpose

This repository provides a realistic simulation environment to prepare for **CodeSignal's Industry Coding Framework (ICF)** assessments. It mirrors the actual test format with multi-level coding problems where each subsequent level builds upon the previous one.

## 💡 Inspiration

Coding assessments, whether CodeSignal, LeetCode, or others, ultimately come down to **practice**. However, CodeSignal's platform doesn't offer practice tests that closely resemble their actual assessments.

After reading [How hackable are automated coding assessments?](https://yanirseroussi.com/2023/05/26/how-hackable-are-automated-coding-assessments/), the original author came to a realization: **CodeSignal is no different than the SAT**. More practice will definitively boost your score. This repo exists to fill that gap, giving you a realistic practice environment so you can walk into your assessment with confidence.

## 📊 Scoring & What You Need to Pass

### Score to Percentile Conversion

CodeSignal provides a [conversion table](https://support.codesignal.com/hc/en-us/articles/13260678794775-Converting-Historical-Coding-Score-Thresholds-to-Assessment-Score) to translate your score to a percentile ranking.

### A General Guideline

The original author passed the screening for a well-funded fintech startup with a score of **480**, which corresponds to the **82nd percentile**.

| Score | Percentile | Likelihood of Passing |
|-------|------------|----------------------|
| < 450 | < 70% | May struggle with competitive companies |
| 480 | ~82% | Passed startup screening |
| **500+** | **~85%+** | **Safe target for most companies** |

**🎯 Aim for 500+ to confidently pass most company screenings.**

### 💡 Pro Tip: Modularity Matters

CodeSignal's ICF assessments evaluate **modularity** as a scoring factor. Demonstrate your understanding of **SOLID principles**:

- **Encapsulate your data in classes** — Don't just use associative arrays everywhere
- **Think about extensibility** — Each level builds on the previous one
- **Use proper OOP patterns** — Not only does this showcase your software engineering skills, but it makes Levels 3 and 4 significantly easier

For example, instead of storing account data in a nested array, create an `Account` class with methods for deposit, withdraw, and transaction history. When you reach Level 3 (scheduled payments) and Level 4 (account merging), you'll thank yourself.

## 🚀 Usage

### Prerequisites

- PHP 8.2+
- [Composer](https://getcomposer.org/)

### Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/LibreSignal-PHP.git
   cd LibreSignal-PHP
   ```

2. **Install dependencies** (PHPUnit)
   ```bash
   composer install
   ```

### Implementing Your Solution

1. Navigate to the question folder (e.g., `Questions/bank_system/`)
2. Read the problem description in the level markdown files (`level1.md`, `level2.md`, etc.)
3. Implement your solution in the stub class (`Simulation.php` or `InMemoryDatabase.php`)
4. **Start with Level 1 and progress sequentially** — just like the real test!

You can add extra classes to the same file, or drop new files in the question folder. Each folder is a PSR-4 namespace (`LibreSignal\BankSystem`, `LibreSignal\InMemoryDatabase`) so anything you add there autoloads.

### Running Tests

Each level is a PHPUnit group. Run tests for a specific level from the <u>**root directory**</u>:

#### 🏦 Bank System

```bash
# Test a specific level
vendor/bin/phpunit --testsuite bank_system --group level1
vendor/bin/phpunit --testsuite bank_system --group level2
vendor/bin/phpunit --testsuite bank_system --group level3
vendor/bin/phpunit --testsuite bank_system --group level4

# Run all tests for the question
vendor/bin/phpunit --testsuite bank_system
```

#### 🗄️ In-Memory Database

```bash
# Test a specific level
vendor/bin/phpunit --testsuite in_memory_database --group level1
vendor/bin/phpunit --testsuite in_memory_database --group level2
vendor/bin/phpunit --testsuite in_memory_database --group level3
vendor/bin/phpunit --testsuite in_memory_database --group level4

# Run all tests for the question
vendor/bin/phpunit --testsuite in_memory_database
```

Add `--testdox` for a readable, per-test listing. `composer test` runs everything.

#### Checking your work against the reference solution

Each test file has a commented-out `use` line at the top. Uncomment it to point the tests at the reference solution instead of your stub:

```php
// use LibreSignal\BankSystem\SimulationSolution as Simulation;
```

## 📁 Project Structure

```
LibreSignal-PHP/
├── README.md
├── composer.json
├── phpunit.xml
└── Questions/
    ├── bank_system/
    │   ├── level1.md                   # Level 1 requirements
    │   ├── level2.md                   # Level 2 requirements
    │   ├── level3.md                   # Level 3 requirements
    │   ├── level4.md                   # Level 4 requirements
    │   ├── Simulation.php              # Your implementation goes here
    │   ├── SimulationSolution.php      # Reference solution
    │   └── BankSystemTest.php          # Test suite
    ├── in_memory_database/
    │   ├── level1.md
    │   ├── level2.md
    │   ├── level3.md
    │   ├── level4.md
    │   ├── InMemoryDatabase.php        # Your implementation goes here
    │   ├── InMemoryDatabaseSolution.php# Reference solution
    │   └── InMemoryDatabaseTest.php    # Test suite
    ├── storage/                        # Problem statements only (no tests yet)
    │   └── level1.md … level4.md
    └── workers/                        # Problem statements only (no tests yet)
        └── level1.md … level3.md
```

## 🐘 PHP-specific gotchas

A few PHP behaviours that bite in exactly these kinds of problems:

- **Numeric string keys become ints.** `$arr["30"]` is stored under the int key `30`. Cast keys back with `(string)` when you read them out, or your output format breaks.
- **`<=>` on strings can compare numerically.** `"10" <=> "9"` is `1` (numeric), which is not lexicographic. Use `strcmp()` for tie-breaking by id.
- **Sort lexicographically with `SORT_STRING`.** `ksort($arr, SORT_STRING)` avoids the numeric-key surprise above.
- **`usort` is stable** since PHP 8.0, so merging sorted histories works as you'd expect.
- **`intdiv()`** for integer division; `/` returns a float.
- **Named arguments** work in PHP 8: the Level 3 database tests call `setAt(..., timestamp: 100)`, so keep the stub's parameter names.

## 📚 Official Documentation

For a deeper understanding of how CodeSignal's ICF works, refer to the official technical brief:

📄 [Industry Coding Skills Evaluation Framework Technical Brief](https://discover.codesignal.com/rs/659-AFH-023/images/Industry-Coding-Skills-Evaluation-Framework-CodeSignal-Skills-Evaluation-Lab-Short.pdf)

## ⏱️ Test Day Tips

1. **Read ALL levels first** — Understanding what's coming helps you design a modular solution from the start
2. **Don't over-engineer Level 1** — But do set up proper data structures
3. **Test frequently** — Run the test suite after implementing each method
4. **Manage your time** — ~70 minutes total, so roughly 15-20 min per level
5. **Partial credit exists** — If stuck on Level 4, make sure Levels 1-3 are solid

## 🤝 Contributing

Found a bug? Have a new question to add? Contributions are welcome! The `storage` and `workers` questions have problem statements but no stubs or tests yet.

## 🙏 Credits

Problem statements, reference solutions, and tests are ported from [LibreSignal](https://github.com/EricZheng0404/LibreSignal) by Eric Zheng.
