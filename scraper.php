<?php
//many thanks to author of fairfield scraper. rpa 14/092015
set_include_path(get_include_path() . PATH_SEPARATOR . '../scraperwiki-php/');

require 'scraperwiki.php';
date_default_timezone_set('Australia/Hobart');
require 'simple_html_dom.php';

$kcbase = 'http://kingborough.tas.gov.au/';
$dapage = $kcbase . 'page.aspx?u=592';

$html = scraperwiki::scrape($dapage);
$dom = new simple_html_dom();
$dom->load($html);
$dapara = $dom->find("table.uLayoutTable td.uContentListDesc p.noLeading");

print 'number of records' . sizeof($dapara);

foreach ($dapara as $thispara) {
    //<p class="noLeading">
    //  <a href="webdata/resources/files/DAS-2015-42  12-09.pdf" onmouseover="self.status='';return true;" target="_blank">
    //    <img style="vertical-align:middle;margin-right:3px;" alt="pdf" src="/webdata/graphics/mime_pdf.gif">
    //  </a>
    //  <a href="webdata/resources/files/DAS-2015-42  12-09.pdf" onmouseover="self.status='';return true;" target="_blank">164 Roslyn Avenue, Blackmans Bay - Representation expiry date is 25 September 2015</a>
    //  <br>
    //  <span style="padding-left:25px;">Subdivision of one lot and balance</span>
    //</p>    
    $record = array();
    $addressDateAnchor = $thispara->find('a', 1);
    $addressDateText = $addressDateAnchor->plaintext;
    $parts = explode(' - Representation expiry date is', $addressDateText);
    $record['address'] = $parts[0] . ', TAS';
    $expiry = $parts[1];
    $record['on_notice_to'] = date('Y-m-d', strtotime($expiry));
    $record['info_url'] = $kcbase . $addressDateAnchor->href;
    //there's probably a clever way to do this
    $record['council_reference'] = explode(' ', trim(strrchr($record['info_url'], '/'), '/'))[0];
    $descriptionspan = $thispara->find('span', 0);
    $record['description'] = $descriptionspan->plaintext;
    $record['date_scraped'] = date('Y-m-d');
    $record['comment_url'] = 'mailto:kc@kingborough.tas.gov.au';

//    var_dump($record);
    
//    $existingRecords = scraperwiki::select("* from data where `council_reference`='" . $record['council_reference'] . "'");
//    if (count($existingRecords) == 0) {
//        print ("Saving record " . $record['council_reference'] . "\n");
        //print_r ($record);
        scraperwiki::save_sqlite(array('council_reference'), $record, 'data');
//    } else {
//        print ("Skipping already saved record " . $record['council_reference'] . "\n");
//    }
}
?>
