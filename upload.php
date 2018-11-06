<?php
/* データベース設定諸々 */
$db_name = 'DB_name';
$host = 'localhost';
$usr = 'user_name';
$passwd = 'password';
function getDb($db_name, $host, $usr, $passwd){
  try{
    $db = new PDO("mysql:dbname={$db_name}; host={$host}; charset=utf8;", $usr, $passwd);
    $db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
  }catch(PDOException $e){
    die("にゃーん:{$e->getMessage()}");
  }
  return $db;
}

/* テーブル名指定*/
$table_name = 'TableName';

/* テーブルの存在確認 */
$db = getDb($db_name, $host, $usr, $passwd);
$stt = $db->prepare("show tables LIKE '{$table_name}'");
$stt->execute();
$tables = $stt->fetch();
if(empty($tables)){
  echo 'テーブルが存在しないので作成：テーブル名'.$table_name."\n";
  $stt = $db->prepare("
  CREATE TABLE `{$table_name}` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `PostNum` char(7) DEFAULT NULL,
  `PrefectureKana` varchar(10) DEFAULT NULL,
  `CityKana` varchar(100) DEFAULT NULL,
  `TownKana` varchar(100) DEFAULT NULL,
  `Prefecture` varchar(10) DEFAULT NULL,
  `City` varchar(200) DEFAULT NULL,
  `Town` varchar(200) DEFAULT NULL,
  `systime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=124251 DEFAULT CHARSET=utf8;
    ");
    $stt->execute();

  }

/* 大元のファイルパスとタイムスタンプ */
$filePath = "FilePath";
$time =  date("YmdHis");

//ディレクトリの存在確認
if(!file_exists("{$filePath}")){
  mkdir("{$filePath}");
}

/*  ファイル名変更 */
$FileName = $_FILES['file']['name'];
$newFileName = $time.$FileName;
$UploadPath =  $filePath.$newFileName;

if (move_uploaded_file($_FILES["file"]["tmp_name"], $UploadPath)){
  // ファイルアップロード成功
  echo "OK";
}else{
  // ファイルアップロード失敗
  echo "失敗";
}

/* 文字コードの変更と諸々 */
$exe = 'iconv -f Shift_JIS -t UTF8 '.$UploadPath.' |';
$exe .= "awk -v RS='\r\n' '".'BEGIN {FS=",";OFS=","}{print$3,$4,$5,$6,$7,$8,$9,"\""strftime("%Y-%m-%d %H:%M:%S",systime())"\""}'."' > ";
$encfileName = $newFileName."encode.csv";
$encFilePath = $filePath.$encfileName;
$exe .= $encFilePath;
exec($exe);

/* sql文の存在確認 */
$sqlFileName = "{$newFileName}.sql";
$sqlFilePath = "{$filePath}{$sqlFileName}";
if(!file_exists("{$sqlFilePath}")){
  touch("{$sqlFilePath}");
  $a = fopen("$sqlFilePath", "w");
  @fwrite($a, " truncate table `{$table_name}`; LOAD DATA LOCAL INFILE '{$encFilePath}' INTO TABLE `{$table_name}` FIELDS TERMINATED BY ',' ENCLOSED BY '".'"'."' (PostNum, PrefectureKana, CityKana, TownKana, Prefecture, City, Town, systime); ");
  fclose($a);
}

/* DBインポートするsqlファイルの実行 */
$exe = " mysql -u {$usr} -p{$passwd} {$db_name} < {$sqlFilePath} ";

exec($exe);
exit;
