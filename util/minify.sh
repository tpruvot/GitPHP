#!/bin/bash
#
# minify.sh
#
# Minfies javascript files
#
# @author Christopher Han <xiphux@gmail.com>
# @copyright Copyright (c) 2010 Christopher Han
# @package GitPHP
# @subpackage util
#

JSDIR="js"

JSEXT=".js"
MINEXT=".min.js"
GZEXT=".gz"

CSSDIR="css"
CSSEXT=".css"
MINCSSEXT=".min.css"

rm -fv ${JSDIR}/*${MINEXT}
rm -fv ${CSSDIR}/*${MINCSSEXT}
rm -fv ${JSDIR}/*${GZEXT}
rm -fv ${JSDIR}/ext/*${GZEXT}
rm -fv ${CSSDIR}/*${GZEXT}
rm -fv ${CSSDIR}/ext/*${GZEXT}

if [ "$1" == "clean" ]; then
	exit;
fi

for i in ${JSDIR}/*${JSEXT}; do
	echo "Minifying ${i}..."
	JSMODULE="`basename ${i%$JSEXT}`"
	java -classpath lib/rhino/js.jar:lib/closure/compiler.jar org.mozilla.javascript.tools.shell.Main lib/requirejs/r.js -o name=${JSMODULE} out=${JSDIR}/${JSMODULE}${MINEXT}.tmp baseUrl=${JSDIR} paths.jquery="empty:" paths.qtip="empty:" paths.d3="ext/d3.v2.min" paths.modernizr="ext/modernizr.custom" optimize="closure" preserveLicenseComments="false"
	cat util/jsheader.js ${JSDIR}/${JSMODULE}${MINEXT}.tmp > ${JSDIR}/${JSMODULE}${MINEXT}
	rm -f ${JSDIR}/${JSMODULE}${MINEXT}.tmp
done

for i in ${CSSDIR}/*${CSSEXT}; do
	echo "Minifying ${i}..."
	CSSBASE=${i%$CSSEXT}
	java -classpath lib/rhino/js.jar org.mozilla.javascript.tools.shell.Main lib/requirejs/r.js -o cssIn=${i} out=${CSSBASE}${MINCSSEXT} optimizeCss="standard"
done

for i in ${JSDIR}/*${MINEXT}; do
	gzip -v -c ${i} > ${i}${GZEXT}
	touch ${i} ${i}${GZEXT}
done

for i in ${JSDIR}/ext/jquery*${MINEXT}; do
	gzip -v -c ${i} > ${i}${GZEXT}
	touch ${i} ${i}${GZEXT}
done

gzip -v -c ${JSDIR}/ext/require.js > ${JSDIR}/ext/require.js${GZEXT}
touch ${JSDIR}/ext/require.js ${JSDIR}/ext/require.js${GZEXT}

for i in ${CSSDIR}/*${MINCSSEXT}; do
	gzip -v -c ${i} > ${i}${GZEXT}
	touch ${i} ${i}${GZEXT}
done

gzip -v -c ${CSSDIR}/ext/jquery.qtip.min.css > ${CSSDIR}/ext/jquery.qtip.min.css${GZEXT}
touch ${CSSDIR}/ext/jquery.qtip.min.css ${CSSDIR}/ext/jquery.qtip.min.css${GZEXT}
