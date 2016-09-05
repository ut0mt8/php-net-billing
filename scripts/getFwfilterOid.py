#!/usr/bin/python

import sys

def usage():
  print "Usage %s <phy|vlan> <tri>" % sys.argv[0]
  sys.exit(2)

def gen_oid(filter_name, counter_name):
  filter_oid = '.'.join(str(ord(c)) for c in filter_name)
  counter_oid = '.'.join(str(ord(c)) for c in counter_name)
  return '1.3.6.1.4.1.2636.3.5.2.1.5.'+str(len(filter_name))+'.'+filter_oid+'.'+str(len(counter_name))+'.'+counter_oid+'.2'

if len(sys.argv) != 3:
  usage()

if sys.argv[1] == 'phy':
  oidin = gen_oid(sys.argv[2]+'_in','in')
  oidout = gen_oid(sys.argv[2]+'_out','out')
elif sys.argv[1] == 'vlan':
  oidin = gen_oid('vlan_in', sys.argv[2]+'_in')
  oidout = gen_oid('vlan_out', sys.argv[2]+'_out')
else:
  usage()

print "in  : " + oidin
print "out : " + oidout
