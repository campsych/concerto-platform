sanitizedCode = concerto.test.sanitizeSource(code)
result = eval(parse(text=sanitizedCode))
