'use strict';

concertoPanel.service('TestWizardParam', ["$filter",
  function ($filter) {
    this.getTypeName = function (type) {
      switch (parseInt(type)) {
        case 0:
          return Trans.TEST_WIZARD_PARAM_TYPE_SINGLE_LINE_TEXT;
        case 1:
          return Trans.TEST_WIZARD_PARAM_TYPE_MULTI_LINE_TEXT;
        case 2:
          return Trans.TEST_WIZARD_PARAM_TYPE_HTML;
        case 3:
          return Trans.TEST_WIZARD_PARAM_TYPE_SELECT;
        case 4:
          return Trans.TEST_WIZARD_PARAM_TYPE_CHECKBOX;
        case 5:
          return Trans.TEST_WIZARD_PARAM_TYPE_VIEW;
        case 6:
          return Trans.TEST_WIZARD_PARAM_TYPE_TABLE;
        case 7:
          return Trans.TEST_WIZARD_PARAM_TYPE_COLUMN;
        case 8:
          return Trans.TEST_WIZARD_PARAM_TYPE_TEST;
        case 9:
          return Trans.TEST_WIZARD_PARAM_TYPE_GROUP;
        case 10:
          return Trans.TEST_WIZARD_PARAM_TYPE_LIST;
        case 11:
          return Trans.TEST_WIZARD_PARAM_TYPE_R;
        case 12:
          return Trans.TEST_WIZARD_PARAM_TYPE_COLUMN_MAP;
        case 13:
          return Trans.TEST_WIZARD_PARAM_TYPE_WIZARD;
      }
      return type;
    };

    this.getDefinerTitle = function (param) {
      if (!param)
        return "";
      var info = param.label ? param.label : this.getTypeName(param.type);
      switch (parseInt(param.type)) {
        case 0:
          return Trans.TEST_WIZARD_PARAM_DEFINER_TITLES_SINGLE_LINE.pf(info);
        case 1:
          return Trans.TEST_WIZARD_PARAM_DEFINER_TITLES_MULTI_LINE.pf(info);
        case 2:
          return Trans.TEST_WIZARD_PARAM_DEFINER_TITLES_HTML.pf(info);
        case 3:
          return Trans.TEST_WIZARD_PARAM_DEFINER_TITLES_SELECT.pf(info);
        case 4:
          return Trans.TEST_WIZARD_PARAM_DEFINER_TITLES_CHECKBOX.pf(info);
        case 5:
          return Trans.TEST_WIZARD_PARAM_DEFINER_TITLES_TEMPLATE.pf(info);
        case 6:
          return Trans.TEST_WIZARD_PARAM_DEFINER_TITLES_TABLE.pf(info);
        case 8:
          return Trans.TEST_WIZARD_PARAM_DEFINER_TITLES_TEST.pf(info);
        case 9:
          return Trans.TEST_WIZARD_PARAM_DEFINER_TITLES_GROUP.pf(info);
        case 10:
          return Trans.TEST_WIZARD_PARAM_DEFINER_TITLES_LIST.pf(info);
        case 11:
          return Trans.TEST_WIZARD_PARAM_DEFINER_TITLES_R_CODE.pf(info);
        case 12:
          return Trans.TEST_WIZARD_PARAM_DEFINER_TITLES_COLUMN_MAP.pf(info);
        case 13:
          return Trans.TEST_WIZARD_PARAM_DEFINER_TITLES_WIZARD.pf(info);
      }
      return "";
    };

    this.getSetterTitle = function (param) {
      if (!param)
        return "";
      switch (parseInt(param.type)) {
        case 1:
          return Trans.TEST_WIZARD_PARAM_SETTER_TITLES_TEXTAREA.pf(param.label);
        case 2:
          return Trans.TEST_WIZARD_PARAM_SETTER_TITLES_HTML.pf(param.label);
        case 7:
          return Trans.TEST_WIZARD_PARAM_SETTER_TITLES_COLUMN.pf(param.label);
        case 9:
          return Trans.TEST_WIZARD_PARAM_SETTER_TITLES_GROUP.pf(param.label);
        case 10:
          return Trans.TEST_WIZARD_PARAM_SETTER_TITLES_LIST.pf(param.label);
        case 11:
          return Trans.TEST_WIZARD_PARAM_SETTER_TITLES_R.pf(param.label);
        case 12:
          return Trans.TEST_WIZARD_PARAM_SETTER_TITLES_COLUMN_MAP.pf(param.label);
        case 13:
          return Trans.TEST_WIZARD_PARAM_SETTER_TITLES_WIZARD.pf(param.label);
      }
      return "";
    };

    this.getDefinerSummary = function (param) {
      if (!param)
        return "";
      switch (parseInt(param.type)) {
        case 3:
          if (param.definition == undefined || !param.definition.options)
            return "";
          var info = param.definition.options.length + " - [";
          for (var i = 0; i < param.definition.options.length; i++) {
            if (i > 0)
              info += ",";
            info += param.definition.options[i].label;
          }
          info += "]";
          return Trans.TEST_WIZARD_PARAM_DEFINER_SUMMARIES_SELECT.pf(info);
        case 9:
          if (param.definition == undefined || !param.definition.fields)
            return "";
          var info = param.definition.fields.length + " - [";
          for (var i = 0; i < param.definition.fields.length; i++) {
            if (i > 0)
              info += ",";
            info += param.definition.fields[i].label;
          }
          info += "]";
          return Trans.TEST_WIZARD_PARAM_DEFINER_SUMMARIES_GROUP.pf(info);
        case 10:
          if (param.definition == undefined || param.definition.element == undefined)
            return "";
          var info = this.getTypeName(param.definition.element.type);
          return Trans.TEST_WIZARD_PARAM_DEFINER_SUMMARIES_LIST.pf(info);
        case 12:
          if (param.definition == undefined || !param.definition.cols)
            return "";
          var info = param.definition.cols.length + " - [";
          for (var i = 0; i < param.definition.cols.length; i++) {
            if (i > 0)
              info += ",";
            info += param.definition.cols[i].name;
          }
          info += "]";
          return Trans.TEST_WIZARD_PARAM_DEFINER_SUMMARIES_COLUMN_MAP.pf(info);
      }
      return "";
    };

    this.getSetterSummary = function (param, output) {
      if (!param || !output)
        return "";
      switch (parseInt(param.type)) {
        case 1:
          var summary = output;
          if (summary.length > 100) {
            summary = summary.substring(0, 97) + "...";
          }
          return Trans.TEST_WIZARD_PARAM_SETTER_SUMMARIES_TEXTAREA.pf(summary);
        case 2:
          var summary = output;
          if (summary.length > 100) {
            summary = summary.substring(0, 97) + "...";
          }
          return Trans.TEST_WIZARD_PARAM_SETTER_SUMMARIES_HTML.pf(summary);
        case 7:
          return Trans.TEST_WIZARD_PARAM_SETTER_SUMMARIES_COLUMN.pf(output.table, output.column);
        case 9:
          return Trans.TEST_WIZARD_PARAM_SETTER_SUMMARIES_GROUP.pf(this.getDefinerSummary(param));
        case 10:
          return Trans.TEST_WIZARD_PARAM_SETTER_SUMMARIES_LIST.pf(output.length);
        case 11:
          var summary = output;
          if (summary.length > 100) {
            summary = summary.substring(0, 97) + "...";
          }
          return Trans.TEST_WIZARD_PARAM_SETTER_SUMMARIES_R.pf(summary);
        case 12:
          if (!param.definition.cols)
            return "";
          var info = param.definition.cols.length + " - [";
          for (var i = 0; i < param.definition.cols.length; i++) {
            if (i > 0)
              info += ",";
            var dst = "?";
            if (output.columns != null && output.columns != undefined) {
              var map = output.columns[param.definition.cols[i].name];
              if (map !== null && map != undefined)
                dst = map;
            }
            info += param.definition.cols[i].name + "->" + dst;
          }
          info += "]";
          return Trans.TEST_WIZARD_PARAM_SETTER_SUMMARIES_COLUMN_MAP.pf(info);
      }
      return "";
    };

    this.wizardParamsToTestVariables = function (test, steps, vars) {
      for (var j = 0; j < steps.length; j++) {
        for (var k = 0; k < steps[j].params.length; k++) {
          var param = steps[j].params[k];
          this.serializeParamValue(param);

          var found = false;
          for (var i = 0; i < vars.length; i++) {
            var variable = vars[i];
            if (param.name === variable.name) {
              variable.value = param.value;
              found = true;
              break;
            }
          }
          if (!found) {
            vars.push({
              id: 0,
              name: param.name,
              test: test.id,
              type: 0,
              description: param.description,
              value: param.value,
              passableThroughUrl: param.passableThroughUrl,
              parentVariable: param.testVariable
            });
          }
        }
      }
    };

    this.testVariablesToWizardParams = function (vars, steps) {
      for (var i = 0; i < vars.length; i++) {
        var variable = vars[i];

        for (var j = 0; j < steps.length; j++) {
          for (var k = 0; k < steps[j].params.length; k++) {
            var param = steps[j].params[k];

            if (variable.name === param.name && variable.type == 0) {
              param.value = variable.value;
              this.unserializeParamValue(param);
              break;
            }
          }
        }
      }
    };

    this.serializeParamValue = function (param) {
      try {
        if (param.type == 7 || param.type == 9 || param.type == 10 || param.type == 12 || param.type == 13) {
          if (param.type == 10)
            param.output = this.deobjectifyListElements(param);
          param.value = angular.toJson(param.output);
        } else
          param.value = param.output;
        if (param.value === null)
          throw "null param value (" + param.label + ")";
      } catch (err) {
        switch (parseInt(param.type)) {
          case 4:
            param.value = "0";
            break;
          case 7:
          case 9:
          case 12:
          case 13:
            param.value = "{}";
            break;
          case 10:
            param.value = "[]";
            break;
          default:
            param.value = "";
            break;
        }
      }
    };

    this.unserializeParamValue = function (param) {
      var setDefault = false;

      if (param.value === null) {
        setDefault = true;
      } else {
        try {
          if (param.type == 7 || param.type == 9 || param.type == 10 || param.type == 12 || param.type == 13) {
            param.output = angular.fromJson(param.value);
            if (param.type == 10)
              param.output = this.objectifyListElements(param);
          } else
            param.output = param.value;
        } catch (err) {
          setDefault = true;
        }
      }

      if (setDefault) {
        if (param.type == 7 || param.type == 9 || param.type == 12 || param.type == 13) {
          param.output = {};
        } else if (param.type == 10) {
          param.output = [];
        }
      }
    };

    this.isParamVisible = function (param, parent, values) {
      try {
        if (!param.hideCondition || param.hideCondition === undefined) {
          return true;
        }
        var res = eval(param.hideCondition);
        if (res === true) {
          return false;
        }
      } catch (err) {
      }
      return true;
    };

    this.objectifyListElements = function (param) {
      var elemTypes = [0, 1, 2, 3, 4, 5, 6, 8, 11];
      if (param.type != 10)
        return param.output;
      if (elemTypes.indexOf(param.definition.element.type) != -1) {
        var result = [];
        for (var i = 0; i < param.output.length; i++) {
          result.push({
            value: param.output[i]
          });
        }
        return result;
      }
      return param.output;
    };

    this.deobjectifyListElements = function (param) {
      var elemTypes = [0, 1, 2, 3, 4, 5, 6, 8, 11];
      if (param.type != 10)
        return param.output;
      if (elemTypes.indexOf(param.definition.element.type) != -1) {
        var result = [];
        for (var i = 0; i < param.output.length; i++) {
          result.push(param.output[i].value);
        }
        return result;
      }
      return param.output;
    };
  }
]);