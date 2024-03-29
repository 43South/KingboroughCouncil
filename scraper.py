import os
import scraperwiki
from bs4 import BeautifulSoup
from datetime import datetime


os.environ["SCRAPERWIKI_DATABASE_NAME"] = "sqlite:///data.sqlite"

applications_url = 'https://www.kingborough.tas.gov.au/development/planning-notices/'
html = scraperwiki.scrape(applications_url)
page = BeautifulSoup(html, 'html.parser')
dalist = page.find('table', id='list')
date_scraped = datetime.now().isoformat()
for da in dalist.find_all('tr'):
    cells = da.find_all('td')
    if len(cells) > 0:
        address = cells[0].get_text() + ', Tasmania, Australia'
        on_notice_from = datetime.strptime(cells[1].get_text(), '%d %b %Y').strftime('%Y-%m-%d')
        on_notice_to = datetime.strptime(cells[2].get_text(), '%d %b %Y').strftime('%Y-%m-%d')
        description = cells[3].get_text()
        info_url = cells[4].find('a')['href']
        council_reference = '-'.join(info_url.split('/')[-1].split('-')[0:3])
        record = {
          'council_reference': council_reference,
          'address': address,
          'description': description,
          'info_url': info_url,
          'date_scraped': date_scraped,
          'on_notice_from': on_notice_from,
          'on_notice_to': on_notice_to
        }
        print(record)
        scraperwiki.sqlite.save(unique_keys=['council_reference'], data=record, table_name="data")
