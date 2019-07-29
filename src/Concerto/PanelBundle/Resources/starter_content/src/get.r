for(.name in .dynamicReturns) {
  assign(.name, c.get(.name, posOffset = -1))
}
