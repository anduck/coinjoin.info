<?php
//script to record possible coinjoins from blocks
require_once('cjinfo-config.conf');

try {
	require 'jsonRPCClient.php';
	$bitcoin = new jsonRPCClient('http://'.BITCOIND_RPC_USER.':'.BITCOIND_RPC_PASSWORD.'@'.BITCOIND_RPC_HOST.':'.BITCOIND_RPC_PORT.'/');
} catch (Exception $e) {
	echo 'Error, can\'t load jsonRPCClient.php or connect Bitcoind:'.$e;
	exit();
}




$hash = "00000000000000000b36c1f926e9f4afeac50a580d220c38aacd0185ffc77126"; //hash of block #355000

$f = fopen("possible_cjs.txt","w");

while ($block = $bitcoin->getblock($hash)) {

	echo "Handling block #".$block['height']." (".count($block['tx'])." txs)...\n";
	foreach ($block['tx'] as $transactionhash) {
		
		#TEST!!
		//$transactionhash = "55eac9d4a4159d4ba355122c6c18f85293c19ae358306a3773ec3a5d053e2f1b";
		
		//get the transaction
		//echo $transactionhash;
		$transactiondecodedRAW = $bitcoin->getrawtransaction($transactionhash);
		$transaction = $bitcoin->decoderawtransaction($transactiondecodedRAW);
		
	
		//echo "Block #".$block['height'].", handling TX...\n";
		
		
		$VinDiffAddrs = Array();
		//var_dump($transaction);
		if (isset($transaction['vin'][0]['coinbase'])) continue; //can't be a CJ since this is a coinbase tx
		
		//echo "Handling ".count($transaction['vin'])." vins... ";
		foreach ($transaction['vin'] as $input) {
			$input_raw_tx = $bitcoin->decoderawtransaction($bitcoin->getrawtransaction($input['txid']));
			$VinDiffAddrs[$input_raw_tx['vout'][$input['vout']]['scriptPubKey']['addresses'][0]] = true;
		//	echo ".";
		}
		//echo " got ".count($VinDiffAddrs)." different vin addresses\n";
		$VoutDiffAddrs = Array();
		foreach ($transaction['vout'] as $output) {
			//var_dump($output);
			//we ignore type='multisig' here, since we just want to look at different vout amount and btc amounts
			$VoutDiffAddrs[$output['scriptPubKey']['addresses'][0]] = $output['value']*1e8;
			
		}
		//echo count($VoutDiffAddrs)." vouts\n";
		
		//test if this can be a cj
		if (count($VoutDiffAddrs)<count($VinDiffAddrs)) continue; //can't be a CJ: too little vouts
		
		//find CJ-looking outputs: multiple same size outputs.
		$VoutAmounts = Array();
		foreach ($VoutDiffAddrs as $amount) {
			if (isset($VoutAmounts[$amount]))
				$VoutAmounts[$amount]++;
			else
				$VoutAmounts[$amount] = 1;
		}
		rsort($VoutAmounts);

		if ($VoutAmounts[0]<2) continue; //all outputs are different size, cant be cj
		
		if (!(count($VinDiffAddrs)>=$VoutAmounts[0])) continue; //must be more or equal cjers than outputs
		
		echo "Possible CJ: ".$transactionhash."\n";
		fwrite($f,"block #".$block['height'].": ".$transactionhash."\n");
		//if (count($VinDiffAddrs)>=count($VoutDiffAddrs))
		//echo "Possible CJ: ".$transactionhash."\n";
		
		
	//	break; //remove after test
	}
//break; //remove after test

if (!isset($block['nextblockhash'])) break;
$hash = $block['nextblockhash'];
}
fclose($f);
echo "Finished at #".$block['height'].".\n";









?>
