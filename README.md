# Simple PHP scripts for reading inputs and create financial statements

Input:
- JSON from https://github.com/HNygard/renamefile-server-nodejs (JSON creator mode)
- Bank account statements (API version of https://github.com/HNygard/sparebank1_statementparser)
- Manaual transactions in JSON files (*manual-transactions.json)

Output:
- HTML files (/regnskap/index.html and /regnskap/*)

Setup:
- ln -s `pwd`/regnskap-to-html.php ~/bin/regnskap-to-html

Run from inside a folder with financial documents:
- regnskap-to-html

TODO:
- Handle VAT
