<?php

// jpeg2swf Version 0.1
//
// by Andreas Windischer
// email: andreas_@gmx.net
// http://jpeg2swf.sourceforge.net

function jpeg2swf($jpeg)
{
	define("TEM",1);
	define("SOF0",192);
	define("SOF1",193);
	define("SOF2",194);
	define("SOF3",195);
	define("SOF5",197);
	define("SOF6",198);
	define("SOF7",199);
	define("SOF9",201);
	define("SOF10",202);
	define("SOF11",203);
	define("SOF13",205);
	define("SOF14",206);
	define("SOF15",207); 
	define("DHT",196);
	define("JPG",200);
	define("DAC",204);
	define("RST0",208);
	define("RST1",209);
	define("RST2",210); 
	define("RST3",211); 
	define("RST4",212); 
	define("RST5",213); 
	define("RST6",214); 
	define("RST7",215); 
	define("SOI",216);  
	define("EOI",217); 
	define("SOS",218); 
	define("DQT",219); 
	define("DNL",220); 
	define("DRI",221); 
	define("DHP",222); 
	define("EXP",223); 
	define("APP0",224); 
	define("APP15",239);
	define("JPG0",240); 
	define("JPG13",253);
	define("COM",254); 


	function myceil($val)
	{
		$ival=round($val-0.5);
		if ($ival!=$val) $ival++;
		return $ival;
	}

	function int2bin($lValue, $nBits)
	{
		if ($lValue<0) $lValue=256*256+$lValue;
		$szBin=decbin($lValue);
		while (strlen($szBin)<$nBits)
		$szBin="0".$szBin;
		$szBin=substr($szBin,-$nBits);
		return $szBin;
	}

	function writeout_int($i)
	{
		$swf2=chr($i%256);
		$swf2.=chr(abs(round($i/256-0.5)));
		return $swf2;
	}

	function writeout_long($i)
	{
		$buf=dechex($i);
		while (strlen($buf)<8)
		$buf="0".$buf;
		$swf2="";
		for ($n=0;$n<4;$n++)
		$swf2.=chr(hexdec(substr($buf,6-$n*2,2)));
		return $swf2;
	}

	function writeoutRect($ulWidth, $ulHeight, $nBits)
	{
		$nBytes=myceil((4 * $nBits + 5) / 8);
		$buf=int2bin($nBits,5);
		$buf.=int2bin(0,$nBits);
		$buf.=int2bin($ulWidth, $nBits);
		$buf.=int2bin(0,$nBits);
		$buf.=int2bin($ulHeight, $nBits, $szBin);
		while (strlen($buf)<(8*$nBytes))
		$buf.="0";
		$swf2="";
		for ($n=0;$n<=$nBytes-1;$n++)
		$swf2.=chr(bindec(substr($buf,$n*8,8)));
		return $swf2;
	}

	//main

	$swf="";
	$lJPEGFileLength=strlen($jpeg);
  
	if (substr($jpeg,6,4)=="JFIF")
	{
		$ulOff=2;
		$bFinished=FALSE;
		while(!$bFinished && ($ulOff < $lJPEGFileLength))
		{
			$ulLen = (ord(substr($jpeg,$ulOff+2,1))*256) + ord(substr($jpeg,$ulOff+3,1)) + 2;
			switch(ord(substr($jpeg,$ulOff + 1,1)))
			{
				case SOF0:
				case SOF1:
				{
					$ulHeight=(ord(substr($jpeg,$ulOff + 5,1))*256+ord(substr($jpeg,$ulOff + 6,1)))*20;
					$ulWidth =(ord(substr($jpeg,$ulOff + 7,1))*256+ord(substr($jpeg,$ulOff + 8,1)))*20;
					break;
				}
				case SOF2:
				case SOF3:
				case SOF5:
				case SOF6:
				case SOF7:
				case SOF9:
				case SOF10:
				case SOF11:
				case SOF13:
				case SOF14:
				case SOF15:break;
				case SOS:  $bFinished=TRUE; break;
				case APP0: break;
				case COM:  break;
				case RST0:
				case RST1:
				case RST2:
				case RST3:
				case RST4:
				case RST5:
				case RST6:
				case RST7:
				case TEM:  break;
				default:   break;
			}
			$ulOff+=$ulLen;
		}
		
		$nBits = 31;
		$ulExp = 1 << $nBits;
		while($nBits >= 0)
		{
			if (($ulHeight & $ulExp) || ($ulWidth & $ulExp)) 
				break;
			$ulExp >>= 1;
			--$nBits;
		}
		$nBits += 2;
		$swf.="FWS";
		$swf.=chr(4);
		$swf.=chr(0);
		$swf.=chr(0);
		$swf.=chr(0);
		$swf.=chr(0);
		$swf.=writeoutRect($ulWidth, $ulHeight, $nBits);
		$swf.=writeout_int(12*256); // FRAMERATE
		$swf.=writeout_int(1);  // frame count
		$swf.=chr(67);
		$swf.=chr(2);

		$swf.=chr(0); // R
		$swf.=chr(0); // G BgColor
		$swf.=chr(0); // B

		// TAG DEFINEBITSJPEG2
		$swf.=chr(127);
		$swf.=chr(5);
		$swf.=writeout_long($lJPEGFileLength+6);
		// bitmap id (1)
		$swf.=writeout_int(1);
		// JPEG encoding table
		$swf.=chr(255);
		$swf.=chr(217);
		$swf.=chr(255);
		$swf.=chr(216);

		// JPEG image
		$swf.=$jpeg;

		// TAG DEFINESHAPE
		// id (2)
		$swf.=chr(191);
		$swf.=chr(0);

		$lTagLen = 2; // shape id len
		$lTagLen += myceil((4 * $nBits + 5) / 8); // rect len
		$lTagLen += 1 + 1 + 2 + 8 + 1; // fills len
		$lTagLen += myceil((6 + 5 + 2*$nBits + 1 + (8 + $nBits)*4 + 6) / 8); // subs len
		$swf.=writeout_long($lTagLen);

		$swf.=writeout_int(2,$swf);
		$swf.=writeoutRect($ulWidth, $ulHeight, $nBits);
		$swf.=chr(1);
		$swf.=chr(65);
		$swf.=writeout_int(1);

		// hasscale, scalebits, scale 20:20 (1:1), norotate, notranslate
		$swf.=chr(217);
		$swf.=chr(64);
		$swf.=chr(0);
		$swf.=chr(5);
		$swf.=chr(0);
		$swf.=chr(0);
		$swf.=chr(0);
		$swf.=chr(0);

		$swf.=chr(16);
		$nLength = myceil((6.0 + 5.0 + 2*$nBits + 1.0 + (8.0 + $nBits)*4 + 6.0) / 8.0);
		$nSubBits = $nBits - 2;
		$szBin="000101";
		$szBin.=int2bin($nBits, 5);
		$szBin.=int2bin($ulWidth, $nBits);
		$szBin.=int2bin($ulHeight, $nBits);
		$szBin.="1";
		$szBin.="11"; $szBin.=int2bin($nSubBits, 4); $szBin.="00";
		$szBin.=int2bin(-$ulWidth, $nBits);
		$szBin.="11"; $szBin.=int2bin($nSubBits, 4); $szBin.="01";
		$szBin.=int2bin(-$ulHeight, $nBits);
		$szBin.="11"; $szBin.=int2bin($nSubBits, 4); $szBin.="00";
		$szBin.=int2bin($ulWidth, $nBits);
		$szBin.="11"; $szBin.=int2bin($nSubBits, 4); $szBin.="01";
		$szBin.=int2bin($ulHeight, $nBits);
		$szBin.="000000";
		while (strlen($szBin)<(8*$nLength))
			$szBin.="0";
		for ($n=0;$n<=$nLength-1;$n++)
			$swf.=chr(bindec(substr($szBin,$n*8,8)));

		// TAG PLACEOBJECT
		// id (26) and length (6)
		$swf.=chr(134);
		$swf.=chr(6);
		// hasmatrix, hascharacter
		$swf.=chr(6);
		// depth = 1
		$swf.=writeout_int(1);
		// characterid = 2
		$swf.=writeout_int(2);
		// empty matrix
		$swf.=chr(0);

		// TAG SHOWFRAME
		// id (1) and length (0)
		$swf.=chr(64);
		$swf.=chr(0);

		// TAG END
		// id (0) and length (0)    
		$swf.=chr(0);
		$swf.=chr(0);

		// update file size
		$lFileLen = strlen($swf);
		$swf=substr_replace($swf,writeout_long($lFileLen), 4, 4);
	}
      
	return $swf;       
}
?>