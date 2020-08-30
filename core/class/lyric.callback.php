<?php
require_once 'lyric.php';

$ret = lyric::callBack();
if ( $ret[lyric::SUCCESS] ) {
	echo "<script>window.close();</script>";
} else {
	echo lyric::i18n("Erreur en récupération du code ou du token : code='{0}', message='{1}'", [$ret["code"],$ret["message"]]);
}
