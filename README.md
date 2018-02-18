# Simple PHP scripts for reading inputs and create financial statements

Input:
- CSV with postings from E-conomic
- JSON from https://github.com/HNygard/renamefile-server-nodejs (JSON creator mode)
- Bank account statements (API version of https://github.com/HNygard/sparebank1_statementparser)

Output:
- HTML files (/regnskap/index.html and /regnskap/*)

Setup:
- ln -s `pwd`/regnskap-to-html.php ~/bin/regnskap-to-html


# TODO

- [ ] Read from JSON files
- [ ] Output a summary
- [ ] Read from CSV files
- [ ] Read from bank account transactions