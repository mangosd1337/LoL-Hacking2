from idc import BADADDR, INF_BASEADDR, SEARCH_DOWN, FUNCATTR_START, FUNCATTR_END
import idc
import idaapi

def main():
	xrefs = XrefsTo(0xF27B90)
	for xref in xrefs:
		offset =  idc.GetOperandValue((xref.frm - 0xF), 0)
		print "%s," % (GetString(offset, -1, ASCSTR_C))

	print "done"

main()