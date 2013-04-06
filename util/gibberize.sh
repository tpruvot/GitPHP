#!/bin/bash
#
# generates gibberish locale for i18n testing
#
# @author Christopher Han <xiphux@gmail.com>
# @copyright Copyright (c) 2012 Christopher Han
# @package GitPHP
# @subpackage Util
#

LOCALESRC="locale/gitphp.pot"
ZZDIR="locale/zz_Debug"
ZZPO="${ZZDIR}/gitphp.po"

if [ -e "${ZZPO}" ]; then
	rm "${ZZPO}"
fi

mkdir -p "${ZZDIR}"

cat "${LOCALESRC}" | \
while read -r LINE; do

	if [[ $LINE == '# SOME DESCRIPTIVE TITLE'* ]]; then
		echo "# GitPHP" >> "${ZZPO}"
		continue
	fi

	if [[ $LINE == '# Copyright'* ]]; then
		echo "# Copyright (C) 2012 Christopher Han" >> "${ZZPO}"
		continue
	fi

	if [[ $LINE == '# This file is distributed'* ]]; then
		echo "# This file is distributed under the same license as the GitPHP package." >> "${ZZPO}"
		continue
	fi

	if [[ $LINE == '# FIRST AUTHOR'* ]]; then
		echo "# Christopher Han <xiphux@gmail.com>, 2012." >> "${ZZPO}"
		continue
	fi

	if [[ $LINE == '#, fuzzy'* ]]; then
		continue
	fi

	if [[ $LINE == '"PO-Revision-Date:'* ]]; then
		echo "\"PO-Revision-Date: `date +\"%Y-%m-%d %H:%M%z\"`\n\"" >> "${ZZPO}"
		HEADER=1
		continue
	fi

	if [[ $LINE == '"Last-Translator:'* ]]; then
		echo "\"Last-Translator: Christopher Han <xiphux@gmail.com>\n\"" >> "${ZZPO}"
		HEADER=1
		continue
	fi

	if [[ $LINE == '"Language-Team:'* ]]; then
		echo "\"Language-Team: Christopher Han <xiphux@gmail.com>\n\"" >> "${ZZPO}"
		HEADER=1
		continue
	fi

	if [[ $LINE == '"Language:'* ]]; then
		echo "\"Language: Gibberish\n\"" >> "${ZZPO}"
		HEADER=1
		continue
	fi

	if [[ $LINE == '"Plural-Forms:'* ]]; then
		echo "\"Plural-Forms: nplurals=2; plural=n != 1;\n\"" >> "${ZZPO}"
		HEADER=1
		continue
	fi

	if [[ $LINE == 'msgid '* ]]; then

		if [[ $HEADER != 1 ]]; then
			echo "${LINE}" >> "${ZZPO}"
			continue
		fi

		MSG="${LINE}"

		while read -r LINE; do
			if [[ "$LINE" == "" ]] || [[ "$LINE" == '#'* ]]; then
				break
			fi
			if [[ ! "$LINE" == 'msgstr'* ]]; then
				MSG="${MSG}\n${LINE}"
			fi
		done

		echo -e "${MSG}" >> "${ZZPO}"

		MSGSTR="$MSG"

		if [[ "${MSG}" == *'msgid_plural'* ]]; then
			MSGSTR="${MSGSTR/msgid_plural/msgstr[1]}"
			MSGSTR="${MSGSTR/msgid/msgstr[0]}"
		else
			MSGSTR="${MSGSTR/msgid/msgstr}"
		fi

		echo -e "${MSGSTR}" | \
		while read -r LINE2; do
			PREFIX="`echo "${LINE2}" | sed -e 's/".*//'`"
			STRING="`echo "${LINE2}" | sed -e 's/^[^"]*"//' | sed -e 's/"$//'`"

			if [[ "${STRING}" == "English" ]]; then

				# special locale name token
				STRING="Gibberish"

			elif [[ ! "${STRING}" == "" ]]; then

				# pad string 30% to account for longer languages
				STRPADDING=$((${#STRING}*30/100))
				STRPADDING=$((${STRPADDING}+1))		# because of integer truncation
				for ((i=1; i <= STRPADDING; i++)); do
					STRING="${STRING}•"
				done

				# add non-english characters
				STRING="${STRING//a/â}"
				STRING="${STRING//e/ȩ}"
				STRING="${STRING//i/ȉ}"
				STRING="${STRING//o/ø}"
				STRING="${STRING//u/ü}"
				STRING="${STRING//A/Å}"
				STRING="${STRING//E/Ȅ}"
				STRING="${STRING//I/Ĭ}"
				STRING="${STRING//O/Ɵ}"
				STRING="${STRING//U/Ṳ}"

				# flag inserted tokens with asterisks
				STRING="`echo "${STRING/\$/\\\$}" | sed -e 's/\(%[1-9]\(\$[a-z]\)\?\)/\*\1\*/g'`"

				# add boundary markers
				STRING="{${STRING}}"
			fi

			echo "${PREFIX}\"${STRING}\"" >> "${ZZPO}"
		done

	fi

	echo "${LINE}" >> "${ZZPO}"
done
