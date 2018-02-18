# Simple PHP scripts for reading inputs and create financial statements

Input:
- JSON from https://github.com/HNygard/renamefile-server-nodejs (JSON creator mode)
- Bank account statements (API version of https://github.com/HNygard/sparebank1_statementparser)

Output:
- HTML files (/regnskap/index.html and /regnskap/*)

Setup:
- ln -s `pwd`/regnskap-to-html.php ~/bin/regnskap-to-html


# TODO
- [ ] Output a warning list for unknown transactions etc
- [ ] Output a summary