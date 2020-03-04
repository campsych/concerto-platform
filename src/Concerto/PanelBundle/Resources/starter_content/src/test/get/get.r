global = global == 1

for(.name in .dynamicReturns) {
  assign(.name, c.get(.name, flowIndexOffset = -1, global = global))
}
