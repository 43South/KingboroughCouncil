<?php
require_once 'vendor/autoload.php';
require_once 'vendor/openaustralia/scraperwiki/scraperwiki.php';

use PGuardiario\PGBrowser;
use Sunra\PhpSimple\HtmlDomParser;

date_default_timezone_set('Australia/Hobart');

$url_base = 'https://www.kingborough.tas.gov.au/development/planning-notices/';
$comment_url = 'mailto:kc@kingborough.tas.gov.au';

$browser = new PGBrowser();
$page    = $browser->get($url_base);

$dom = HtmlDomParser::str_get_html($page->html);

foreach ( $dom->find("table.table",0)->children(1)->find('tr') as $tr ) {
    $council_reference = strrev(explode("/", strrev($tr->find("a",0)->href))[0]);                             # get the file name
    $council_reference = explode("-", $council_reference);                                                    # split up
    $council_reference = $council_reference[0] . '-' . $council_reference[1] . '-' . $council_reference[2];   # only pickup the first three fields

    # Put all information in an array
    $record = [
        'council_reference' => $council_reference,
        'address'           => trim(htmlspecialchars_decode($tr->find("td",0)->plaintext)) . ', Tasmania',
        'description'       => trim(htmlspecialchars_decode($tr->find("td",3)->plaintext)),
        'info_url'          => $url_base,
        'comment_url'       => $comment_url,
        'date_scraped'      => date('Y-m-d'),
        'on_notice_from'    => date('Y-m-d', strtotime($tr->find("td",1)->plaintext)),
        'on_notice_to'      => date('Y-m-d', strtotime($tr->find("td",2)->plaintext))
    ];

    # Check if record exist, if not, INSERT, else do nothing
    $existingRecords = scraperwiki::select("* from data where `council_reference`='" . $record['council_reference'] . "'");
    if ( count($existingRecords) == 0 ) {
        print ("Saving record " . $record['council_reference'] . " - " . $record['address'] ."\n");
//         print_r ($record);
        scraperwiki::save(array('council_reference'), $record);
    } else {
        print ("Skipping already saved record - " . $record['council_reference'] . "\n");
    }
}
