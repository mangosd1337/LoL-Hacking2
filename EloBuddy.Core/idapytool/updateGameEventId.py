from idc import BADADDR, INF_BASEADDR, SEARCH_DOWN, FUNCATTR_START, FUNCATTR_END
import idc
import idaapi

def main():
	begin = 0x00527D41;
	end = 0x0052D43C;

	ea = begin;

	while ea != end:
		mnem = GetMnem(ea)
		stackValue1 = GetOperandValue(ea, 0)
				
		if mnem == "push" and stackValue1 == 8:
			stringAddr = GetOperandValue((ea+0x2), 1)
			eventName = GetString(stringAddr, -1, ASCSTR_C)

			print "%s," % eventName
		

		ea = NextNotTail(ea)

main()