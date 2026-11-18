#!/usr/bin/env bash
set -euo pipefail

key=${1:?Clé SSH manquante}
known_hosts=${2:?Fichier known_hosts manquant}
mode=${3:-deploy}
target=u262228329@185.166.188.94
domain=/home/u262228329/domains/edenolam.com
public="$domain/public_html"
files=(index.php sendmail.php robots.txt sitemap.xml)
directories=(css img js phpmailer)

[[ $mode == deploy || $mode == --dry-run ]]
cd "$(dirname "${BASH_SOURCE[0]}")/.."

"${PHP_BIN:-php}" -l index.php
"${PHP_BIN:-php}" -l sendmail.php
for file in "${files[@]}"; do
    if [[ ! -f $file || -L $file ]]; then
        printf "Fichier de déploiement manquant ou invalide : %s\n" "$file" >&2
        exit 1
    fi
done
for directory in "${directories[@]}"; do
    [[ -d $directory && ! -L $directory ]]
    [[ -z $(find "$directory" -type l -print -quit) ]]
done

ssh_options=(-F /dev/null -i "$key" -p 65002 -o BatchMode=yes
    -o IdentitiesOnly=yes -o StrictHostKeyChecking=yes
    -o "UserKnownHostsFile=$known_hosts" -o ConnectTimeout=10)
ssh "${ssh_options[@]}" "$target" "test -s '$domain/private/portfolio-smtp-password'"

printf -v transport '%q ' ssh "${ssh_options[@]}"
options=(-rc --itemize-changes -e "$transport")
if [[ $mode == --dry-run ]]; then
    options+=(--dry-run)
fi

for directory in "${directories[@]}"; do
    rsync "${options[@]}" --delete -- "$directory/" "$target:$public/$directory/"
done
rsync "${options[@]}" -- "${files[@]}" "$target:$public/"
