import requests
import os
import sys
from google.transit import gtfs_realtime_pb2
from google.protobuf.json_format import MessageToJson

from dotenv import load_dotenv

def main():
    if len(sys.argv) != 2:
        return
    
    endpoint = sys.argv[1]

    load_dotenv()
    header = {'x-api-key': os.getenv('MTA_API_KEY')}

    x = requests.get(endpoint, headers=header)

    feed = gtfs_realtime_pb2.FeedMessage()
    feed.ParseFromString(x.content)

    json = MessageToJson(feed)
    print (json)
    return

if __name__=="__main__":
    main()