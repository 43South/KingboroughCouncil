<?php
//many thanks to author of fairfield scraper. rpa 14/09/2015
//update to work with new council website. rpa 2/1/2019

set_include_path(get_include_path() . PATH_SEPARATOR . '../scraperwiki-php/');

require 'scraperwiki.php';
date_default_timezone_set('Australia/Hobart');
require 'simple_html_dom.php';

$kcbase = 'http://kingborough.tas.gov.au/';
$dapage = $kcbase . 'development/planning-notices/';

$dateformat = 'Y-m-d';

$html = scraperwiki::scrape($dapage);
$dom = new simple_html_dom();
$dom->load($html);
//I was using table>tbody>tr but the parser seems to ignore the tbody and keeps picking up the heading, so I'll just cut it off instead.
$dapara = $dom->find("table > tr");
$dapara = array_slice($dapara, 1);

print 'number of records: ' . sizeof($dapara);

foreach ($dapara as $thispara) {
    //<p class="noLeading">
    //  <a href="webdata/resources/files/DAS-2015-42  12-09.pdf" onmouseover="self.status='';return true;" target="_blank">
    //    <img style="vertical-align:middle;margin-right:3px;" alt="pdf" src="/webdata/graphics/mime_pdf.gif">
    //  </a>
    //  <a href="webdata/resources/files/DAS-2015-42  12-09.pdf" onmouseover="self.status='';return true;" target="_blank">164 Roslyn Avenue, Blackmans Bay - Representation expiry date is 25 September 2015</a>
    //  <br>
    //  <span style="padding-left:25px;">Subdivision of one lot and balance</span>
    //</p>    

//    <td>27 Cox Drive, Dennes Point</td>
//    <td>12 Dec 2018</td>
//    <td>3 Jan 2019</td>
//                                        
//    <td>Extension to dwelling (including deck) alterations and outbuilding (carport)</td>
//
//    <td>
//    <a style="margin-bottom: 3px; margin-right: 3px;" href="https://www.kingborough.tas.gov.au/wp-content/uploads/2019/01/DA-2018-550-27-Cox-Drive.pdf" class="btn-sm btn btn-primary">Application documentation</a>                                        </td>

    $record = array();
    $info_url = $thispara->children(4) -> find("a",0)->href;
    $ref_start = strpos($info_url, "/DA");
    $file_name = substr($info_url, $ref_start + 1); // +1 to drop the leading slash
    //this is an ugly way of finding the first three hyphen delimited fields in the filename
    $council_ref = implode("-", array_slice(explode("-", $file_name), 0, 3));
    $record['council_reference'] = $council_ref;
    $record['address'] = $thispara->children(0) -> innertext . ', Tas';
    $record['description'] = $thispara->children(3) -> innertext;
    $record['info_url'] = $info_url;
    $record['comment_url'] = 'mailto:kc@kingborough.tas.gov.au';
    $record['date_scraped'] = date($dateformat);
//    $record['date_received'] = 
    //this date format conversion is a bit fragile, but we'll have to be brave
    $record['on_notice_from'] = date($dateformat, strtotime($thispara->children(1)->innertext));
    $record['on_notice_to'] = date($dateformat, strtotime($thispara->children(2)->innertext));

    $existingRecords = scraperwiki::select("* from data where `council_reference`='" . $record['council_reference'] . "'");
    if (count($existingRecords) == 0) {
        print ("Saving record " . $record['council_reference'] . "\n");
//        print_r ($record);
        scraperwiki::save_sqlite(array('council_reference'), $record, 'data');
    } else {
        print ("Skipping already saved record " . $record['council_reference'] . "\n");
    }
}
?>
