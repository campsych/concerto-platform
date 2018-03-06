(function (mod) {
  if (typeof exports == "object" && typeof module == "object") // CommonJS
    mod(require("../../lib/codemirror"));
  else if (typeof define == "function" && define.amd) // AMD
    define(["../../lib/codemirror"], mod);
  else // Plain browser env
    mod(CodeMirror);
})(function (CodeMirror) {
  "use strict";

  function forEach(arr, f) {
    for (var i = 0, e = arr.length; i < e; ++i)
      f(arr[i]);
  }

  function arrayContains(arr, item) {
    if (!Array.prototype.indexOf) {
      var i = arr.length;
      while (i--) {
        if (arr[i] === item) {
          return true;
        }
      }
      return false;
    }
    return arr.indexOf(item) != -1;
  }

  var r_function_index = new Array();

  function scriptHint(editor, getToken) {
    console.log(editor);
    if (r_function_index.length == 0) {
      var configuration = editor.getOption('hintOptions').functionIndex;
      for (var i = 0; i < configuration.length; i++)
        r_function_index.push(configuration[i].fun + '()');
    }


    // Find the token at the cursor
    var cur = editor.getCursor(), token = getToken(editor, cur), tprop = token;
    // If it's not a 'word-style' token, ignore the token.

    if (!/^[\w$_\.]*$/.test(token.string)) {
      token = tprop = {
        start: cur.ch, end: cur.ch, string: "", state: token.state,
        className: null
      };
    }

    if (!context)
      var context = [];
    context.push(tprop);

    var completionList = getCompletions(token, context);
    completionList = completionList.sort();

    return {
      list: completionList,
      from: CodeMirror.Pos(cur.line, token.start),
      to: CodeMirror.Pos(cur.line, token.end)
    };
  }

  function rHint(editor) {
    return scriptHint(editor, function (e, cur) {
      return e.getTokenAt(cur);
    });
  }

  CodeMirror.registerHelper("hint", "r", rHint);

  function getCompletions(token, context) {

    var found = [], start = token.string;

    function maybeAdd(str) {
      if (str.lastIndexOf(start, 0) == 0 && !arrayContains(found, str))
        found.push(str);
    }

    function gatherCompletions(_obj) {
      forEach(r_function_index, maybeAdd);
    }

    if (context) {
      // If this is a property, see if it belongs to some object we can
      // find in the current environment.
      var obj = context.pop(), base;

      if (obj.type == "variable")
        base = obj.string;
      else if (obj.type == "variable-3")
        base = ":" + obj.string;

      while (base != null && context.length)
        base = base[context.pop().string];
      if (base != null)
        gatherCompletions(base);
    }
    return found;
  }
});

