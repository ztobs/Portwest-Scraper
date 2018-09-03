<?php
// This is a portwest products scraper.
// Update the links.txt file with links to portwest categories (sorry its not automated, ill automate in future)
// Set the excahnge rate from usd to your currency, if your currency is usd then use 1
// Set the price markup in percentage
// Set your email to recieve feeds when completed

/**
 * Usage:
 * php scrap.php
 * Eg:
 * php scrap.php 
 **/

////////////////////////////////////////////////////////////////////////////////
////////////////////////////  VARIABLES  ///////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
$usd_rate = 365;
$markup = 40; // percentage
$cookie = '_ga=GA1.2.419216526.1534847483; frontend_cid=WqSys4GIrBwJPgSA; _gid=GA1.2.205374715.1535893100; _gat=1; frontend=bnk8uec2g6m3n0v91aenc3qs34; currency=USD';
$email = "ztobscieng@gmail.com";  // email to recieve product feed
////////////////////////////////  END  /////////////////////////////////////////



// removing execution limits
error_reporting(E_ALL);
ini_set('max_execution_time', 0);
ini_set('memory_limit', '1024M');

include "functions.php";




// Converting the links in text file to array
$ll = file_get_contents("links.txt", 'r'); 
$links = explode("\n", str_replace("\r", "", $ll));

// Create filename
$fname = "out/products-".date("Ymd-His").".csv";

// Open the file for writing
$fres = fopen($fname, 'w');

$pr = 0; // Product counter
foreach ($links as $link) // looping through category links
{
	// Now we need to loop through pages in each categories
	// To do date we just append ?p= to links till we get to end which is just same page as the last valid page.

	$p = 1;
	$p1 = "";
	while (true) 
	{
		$pagedom = getDom($link."?p=".$p);
		
		// var_dump($pagedom); die();
		// Stops page loop when end is reached
		if($p1 != $pagedom->find(".product-name > a")[0]->plaintext) $p1 = $pagedom->find(".product-name > a")[0]->plaintext;
		else break;

		$cat = substr(str_replace("/", ">", str_replace(" Home /", "", str_replace("  ", "", $pagedom->find("div.breadcrumbs")[0]->plaintext))), 1, -1) ;
		$tags = str_replace(" >", ",", $cat);

		foreach($pagedom->find(".product-name > a") as $plinks) // looping through product links
		{
			$plink = $plinks->href; 
			
			$pdom = getDom($plink);
			
			
			// Retrieve all descriptions into an array
			$desc = []; // Need to reset this in loop
			foreach ($pdom->find("table#product-attribute-specs-table") as $desc_)
			{
				$desc[] = str_replace("<a id='readmore' href='#description'>Read More</a>", "", $desc_->outertext);
			}

			// Retrieve all images into an array
			$images = []; // Need to reset this in loop
			foreach ($pdom->find("div.product-image-gallery img") as $img_)
			{
				$images[] = $img_->src;
			}
			array_shift($images); // There is need to remove the 1st image

			$odesc = str_replace("display: none;", "", $pdom->find("div#description")[0]->outertext);

			$pname = trim($pdom->find("div.product-name")[0]->plaintext);
			$sku = trim($pdom->find("#product-attribute-specs-table td")[0]->plaintext);
			$short_description = str_replace('"', "'", $desc[0]);
			$long_description = str_replace('"', "'", implode("<br>", $desc)."<br>".$odesc);
			$pId = $pdom->find("input[id=pId]")[0]->value;	
			$weight = getWeight($pdom);


			// Lets get price
			$price = getDom2("https://www.portwest.com/portwest/index/getRealTimePrice", "POST", array(),  "pId=".$pId, "form", "json")['response'];
			$price_usd = str_replace("$", "", $price);
			$markup_usd = $price_usd+($markup/100*$price_usd);
			$price_ngn = number_format($usd_rate*$markup_usd, 2, ".", "");

			if($price_ngn == 0) 
				{
					echo "'$pname' skipped, no price\n";
					continue;
				}

			$row_data = array(
				"Type"						=>	"simple",
				"SKU"						=>	$sku,
				"Name"						=>	$pname,
				"Published"					=>	1,
				"Is featured?"				=>	0,
				"Visibility in catalog"		=>	"visible",
				"Short Description"			=>	$short_description,
				"Description"				=>	$long_description,
				"Tax Status"				=>	"none",
				"In stock?"					=>	1,
				"Weight"					=>	$weight,
				"Allow customer reviews?"	=>	1,
				"Purchase Note"				=>	"Thanks for buying",
				"Price"						=>	$price_ngn,
				"Stock"						=>	100,
				"Categories"				=>	$cat,
				"Tags"						=>	$tags,
				"Images"					=>	implode(",", $images),
				"Position"					=>	9
			);
			
			// Lets write the header once
			if(!isset($header)) 
			{
				$header = array_keys($row_data);
				fputcsv($fres, $header);
			}

			// Continue writing row through loop
			fputcsv($fres, $row_data);

			// Not a bad idea to let us know youve writen to file
			echo "'$pname' written to file\n";
			$pr++;

		}
		$p++;
	}
	

}

// close the file
fclose($fres);

echo "\n$pr products completed. ;)";
sendEmail($email, "bots@scrapers.com", "Ztobs Bots", "Portwest Products", "Find Attachment", $fname);






