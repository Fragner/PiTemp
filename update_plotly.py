#! /usr/bin/env python
import plotly
import os


def update_temp(date,temp):
        py = plotly.plotly(username='martin-fragner@utanet.at', key='7ew737sh9i')
        r =  py.plot(date,temp,
        filename='RPiTempCont',
        fileopt='extend',
        layout={'title': 'Raspberry Pi Temperature Status'})

if __name__ == '__main__':
    import sys
    if len(sys.argv) == 3:
        date = sys.argv[1]
        temp = sys.argv[2]
        update_temp(date,temp)
    else:
        print 'Usage: ' + sys.argv[0] + ' date temp'
