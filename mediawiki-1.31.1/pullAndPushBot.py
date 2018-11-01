# python pullAndPushBot [cursor] [soFar]
# cursor is, e.g., 1
# soFar is, e.g., 800000001 (8 million and 1st revision)

import pywikibot
import pprint
import sys
import os.path
from pywikibot import pagegenerators
import operator

class pullAndPushRevisions:
	def __init__(self, test, wikipedia):
			self.siteTest = test
			self.siteWikipedia = wikipedia
			
	def pullAndPush( self, cursor, soFar, increment, howFar ):
		count = soFar
		while 1:
			endCount = soFar + increment * 50
			revids = ""
			firstOne = True
			while count < endCount:
				if firstOne == True:
					firstOne = False
				else:
					revids = revids + "|"
				revids = revids + str(count)
				count = count + 10
			#pprint.pprint(revids)
			soFar = endCount
			#print(endCount)
			#continue
			#sys.exit()
			pullParameters = {
				'action': 'query',
				'prop': 'revisions',
				'rvslots': 'main',
				'rvprop': 'ids|flags|timestamp|user|userid|comment|content|tags',
				#'revids': '123456|123457'
				'revids': revids
			}
			pullGen = pywikibot.data.api.Request(
				self.siteWikipedia, parameters=pullParameters )
			pullData = pullGen.submit()
			#pprint.pprint(data)
			unsortedRevisions=[]
			revisions = []
			if len ( pullData['query']['pages'] ) ==0 :
				print ( "Empty; continuing" )
				continue
			for pageId,page in pullData['query']['pages'].items():
				title = page['title']
				ns = page['ns']
				pageid = page['pageid']
				for thisRevision in page['revisions']:
					thisRevision['title'] = title
					thisRevision['ns'] = ns
					thisRevision['pageid'] = pageid
					unsortedRevisions.append( thisRevision )
			revisions = sorted(unsortedRevisions, key=lambda revision: revision['revid'] )
			#for revision in revisions:
			#	print( revision['revid'] )
			#sys.exit()
			for revision in revisions:
				pushParameters = {
					'action': 'edit',
					'title': 'Revision:' + str(revision['revid']),
					'namespace': revision['ns'],
					'remotetitle': revision['title'],
					'page': revision['pageid'],
					'token': self.siteTest.tokens['edit'],
					'summary': revision['comment'],
					'sdtags': '|'.join(revision['tags']),
					'timestamp': revision['timestamp'],
					'user': revision['user'],
					'userid': revision['userid'],
					'remoterev': revision['revid'],
					'text': revision['slots']['main']['*']
				}
				if 'minor' in revision:
					pushParameters['minor'] = 'true'
				if 'bot' in revision:
					pushParameters['bot'] = 'true'
				pushGen = pywikibot.data.api.Request(
					self.siteTest, parameters=pushParameters )
				pushData = pushGen.submit()
				pprint.pprint(pushData)
				if pushData['edit']['result'] == 'Success':
					cursorFilename = 'cursor' + str(cursor) + '.txt'
					f = open( cursorFilename, 'w')
					f.write( str(revision['revid'] + increment ) + "\n" )
					f.close()
				if howFar == 0:
					sys.exit()
		
siteTest = pywikibot.Site(code='en', fam='test2')
if not siteTest.logged_in():
    siteTest.login()
siteWikipedia = pywikibot.Site(code='en', fam='wikipedia')
cursor = 1
if len(sys.argv) > 1:
	cursor = int(sys.argv[1])
soFar = 0
if len(sys.argv) > 2:
	soFar = int(sys.argv[2])
else:
	cursorFilename = 'cursor' + str(cursor) + '.txt'
	if os.path.isfile( cursorFilename ):
		f = open( cursorFilename, 'r')
		soFar = int( f.readline() )
increment = 10
howFar = increment * 50 # It's only function is that when it's set to 0, we only do one revision
if len(sys.argv) > 3:
	howFar = int(sys.argv[3])
myTestScript = pullAndPushRevisions( siteTest, siteWikipedia )
if ( soFar % increment != cursor ):
	print ( 'Error: Modulus is ' + str( soFar % increment ) )
	sys.exit()
print ( 'Resuming with ' + str( soFar ) )
myTestScript.pullAndPush( cursor, soFar, increment, howFar )