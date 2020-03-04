global = global == 1

for(.name in .dynamicInputs) {
  c.set(.name, get(.name), flowIndexOffset = -1, global = global)
}