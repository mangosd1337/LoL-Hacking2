from idc import BADADDR, INF_BASEADDR, SEARCH_DOWN, FUNCATTR_START, FUNCATTR_END
import idc
import idaapi
import datetime

#ACBEF0

def main():
	xrefs = XrefsTo(0x13070FC)
	for xref in xrefs:
		packetHeader = idc.NextNotTail(xref.frm)
		opCode1 = idc.GetOperandValue(packetHeader, 1)

		if opCode1 > 10 and opCode1 < 16655:
			print "%08x -> %2x" % (packetHeader, opCode1)

		#print "%08x" % packetHeader

main()