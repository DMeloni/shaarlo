#!/bin/bash
SHAARLO_HOST='https://www.shaarlo.fr'

wget "$SHAARLO_HOST/update_table_liens.php" > /dev/null

rm update_table_liens.php.*


