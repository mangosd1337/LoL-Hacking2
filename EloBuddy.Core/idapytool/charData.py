from idc import BADADDR, INF_BASEADDR, SEARCH_DOWN, FUNCATTR_START, FUNCATTR_END
import idc
import idaapi

MOV_PROPERTIES = [ 'RecordAsWard', 'DoesNotGiveMinionScore', 'UseRiotRelationships' ]
FLOAT_PROPERTIES = [ 'HPPerTick', 'XOffset', 'YOffset', 'WorldOffset', 'HPPerLevel', 'MPPerLevel', 'HPRegenPerLevel' ]
FSTP_ARRAY = []

def main():
	print "Parsing CharData..."

	start = 0x00E36855
	end = 0x00E39EC2
	ea = start

	parseOffset = False
	property = ''
	fstpIterator = 0

	# Fill FSTP_Array
	while ea != end:
		mnem = idc.GetMnem(ea)

		if mnem == "fstp":
			FSTP_ARRAY.append(idc.GetOperandValue(ea, 1))

		ea = idc.NextNotTail(ea)

	ea = start

	while ea != end:
		mnem = idc.GetMnem(ea)
		tmpb = False

		# Get property name
		if mnem == 'push':
			stringAddr = idc.GetOperandValue(ea, 0)
			if stringAddr != BADADDR:
				tmpProperty = idc.GetString(stringAddr, -1, ASCSTR_C)
				if tmpProperty in MOV_PROPERTIES or tmpProperty in FLOAT_PROPERTIES:
					property = tmpProperty
					tmpb = True

		if tmpb == True and property != None and len(property) > 4:
			parseOffset = True

		# Get offset
		if parseOffset == True and (mnem == "fstp" or mnem == "lea" or mnem == "mov"):
			
			# Offset
			result = 0

			# Inside fstp[]
			if property in FLOAT_PROPERTIES:
				result = FSTP_ARRAY[fstpIterator]
				fstpIterator += 1

			# Inside mov[]
			if property in MOV_PROPERTIES:
				if idc.GetOpnd(ea, 0) != "edx" and idc.GetOpnd(ea, 0) != "ecx":
					result = {
						'fstp': lambda offset: idc.GetOperandValue(offset, 1),
						'mov':  lambda offset: idc.GetOperandValue(offset, 0),
						'lea':  lambda offset: 0
					}[mnem](ea)

			# Display offset
			if result != 0:
				print "%s => %08x" %  (property, result)
				parseOffset = False

		ea = idc.NextNotTail(ea)

main()