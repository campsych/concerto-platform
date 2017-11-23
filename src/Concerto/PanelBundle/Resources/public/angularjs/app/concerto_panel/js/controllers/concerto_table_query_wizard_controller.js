function ConcertoTableQueryWizardController($scope, $uibModalInstance, $http, $timeout, RDocumentation, DataTableCollectionService, completionWidget, selection, completionContext, completionData) {

  DefaultRCompletionWizardController.call(
      this,
      $scope, $uibModalInstance, $http, $timeout, RDocumentation, completionWidget, selection, completionContext, completionData
  );

  $scope.params = 'list()';
  $scope.n = '-1';


  $scope.tables = DataTableCollectionService;
  $scope.tables.fetchObjectCollection();

  $scope.codemirrorForceRefresh = 1;
  $timeout(function () {
    $scope.codemirrorForceRefresh++;
  }, 20);


  var tmp_codemirror = angular.copy($scope.miniCodemirror);
  tmp_codemirror.mode = 'sql';
  $scope.sqlMiniCodemirror = tmp_codemirror;

  // TODO - refactor it a bit, since there's quite a lot of refactorable repetition

  var escapingEnabled = false;

  var escapeIfRequired = function (string) {
    if (escapingEnabled)
      return "'\",dbEscapeStrings(concerto$connection,toString(" + string + ")),\"'";
    else
      return string;
  }

  // first section - query type
  // I'm reasonably certain that's not likely to change, and it's not gonna get translated :)
  $scope.queryTypes = ['SELECT', 'INSERT', 'UPDATE', 'DELETE'];
  $scope.selectedQueryType = '';

  // insert wizard
  $scope.insertedValues = [];
  $scope.addInsertedValue = function () {
    $scope.insertedValues.push({value: '', useCode: 0});
  };
  $scope.removeInsertedValue = function (position) {
    $scope.insertedValues.splice(position, 1);
  };


  // update wizard
  $scope.updatedValues = [];
  $scope.addUpdatedValue = function () {
    $scope.updatedValues.push({value: '', useCode: 0});
  };
  $scope.removeUpdatedValue = function (position) {
    $scope.updatedValues.splice(position, 1);
  };
  $scope.buildUpdateSql = function (position) {
    $scope.updatedValues[position].wizardCode = $scope.updatedValues[position].variable.name + ' = ' + escapeIfRequired($scope.updatedValues[position].wizardValue);
  }

  // where...
  $scope.whereConditions = [];
  $scope.addWhereCondition = function () {
    $scope.whereConditions.push({value: '', useCode: 0, logicOperator: 'AND', operator: '='});
  };
  $scope.removeWhereCondition = function (position) {
    $scope.whereConditions.splice(position, 1);
  };
  $scope.buildWhereSql = function (position) {
    var where = $scope.whereConditions[position];
    $scope.whereConditions[position].wizardCode = ((position > 0) ? (where.logicOperator + " ") : '') + where.variable.name
        + " " + where.operator + " " + escapeIfRequired(where.wizardValue);
  }

  // columns for select...
  $scope.selectedValues = [{useCode: 0}];
  $scope.addSelectedValue = function () {
    $scope.selectedValues.push({useCode: 0});
  };
  $scope.removeSelectedValue = function (position) {
    $scope.selectedValues.splice(position, 1);
  };
  $scope.buildSelectSql = function (position) {
    $scope.selectedValues[position].wizardCode = ($scope.selectedValues[position].variable) ? $scope.selectedValues[position].variable.name : '*';
  };

  // order by for select...
  $scope.orderingOptions = [];
  $scope.addOrderingOption = function () {
    $scope.orderingOptions.push({useCode: 0, direction: "ASC"});
  };
  $scope.removeOrderingOption = function (position) {
    $scope.orderingOptions.splice(position, 1);
  };
  $scope.buildOrderingSql = function (position) {
    $scope.orderingOptions[position].wizardCode = $scope.orderingOptions[position].variable.name + " " + $scope.orderingOptions[position].direction;
  };

  var extractSelectedColumns = function () {
    var res = '';
    for (var itr = 0; itr < $scope.selectedValues.length; itr++) {
      if (!$scope.selectedValues[itr].useCode)
        $scope.buildSelectSql(itr);

      if (itr)
        res += ", ";

      res += $scope.selectedValues[itr].wizardCode;
    }
    return res;
  }

  var extractWhereConditions = function () {
    if ($scope.whereConditions.length == 0)
      return '';

    var res = " \nWHERE ";
    for (var itr = 0; itr < $scope.whereConditions.length; itr++) {
      if (!$scope.whereConditions[itr].useCode)
        $scope.buildWhereSql(itr);

      if (itr)
        res += " \n";

      res += $scope.whereConditions[itr].wizardCode;
    }
    return res;
  }

  var extractOrderingOptions = function () {
    if ($scope.orderingOptions.length == 0)
      return '';

    var res = " \nORDER BY ";
    for (var itr = 0; itr < $scope.orderingOptions.length; itr++) {
      if (!$scope.orderingOptions[itr].useCode)
        $scope.buildOrderingSql(itr);

      if (itr)
        res += ", ";

      res += $scope.orderingOptions[itr].wizardCode;
    }
    return res;
  }

  var extractUpdatedValues = function () {
    if ($scope.updatedValues.length == 0)
      return '';

    var res = " \nSET ";
    for (var itr = 0; itr < $scope.updatedValues.length; itr++) {
      if (!$scope.updatedValues[itr].useCode)
        $scope.buildUpdateSql(itr);

      if (itr)
        res += ",\n";

      res += $scope.updatedValues[itr].wizardCode;
    }
    return res;
  }

  var extractInsertedValues = function () {

    var resCol = " \n(";
    var resVal = " \nVALUES (";

    var added = 0;
    for (var itr = 0; itr < $scope.insertedValues.length; itr++) {
      if (!$scope.insertedValues[itr].variable) continue;

      var val = "";
      if ($scope.insertedValues[itr].useCode)
        val = $scope.insertedValues[itr].wizardCode;
      else
        val = escapeIfRequired($scope.insertedValues[itr].wizardValue);

      if (itr) {
        resCol += ', ';
        resVal += ', ';
      }

      resCol += $scope.insertedValues[itr].variable.name;
      resVal += val;
      added++;
    }

    resCol += ')';
    resVal += ')';
    return resCol + resVal;
  }

  var getCompleteSQL = function () {
    var query = $scope.selectedQueryType;
    var res = query;
    escapingEnabled = true;

    if (query == 'SELECT')
      res += ' ' + extractSelectedColumns();

    if ((query == 'SELECT') || (query == 'DELETE'))
      res += ' FROM';
    else if (query == 'INSERT')
      res += ' INTO';

    if ($scope.selectedTable == null) return "";
    res += ' ' + $scope.selectedTable.name;

    if (query == 'INSERT')
      res += extractInsertedValues();

    if (query == 'UPDATE')
      res += extractUpdatedValues();

    if (query != 'INSERT')
      res += extractWhereConditions();

    if (query == 'SELECT')
      res += extractOrderingOptions();

    escapingEnabled = false;

    return res;

  }

  $scope.getCompletionData = function () {
    var result = '';
    if ($scope.insertComments)
      result += "# " + $scope.comment + "\n";
    result += RDocumentation.getFunctionName() + "(\n";

    var first = true;
    for (var itr = 0; itr < $scope.arguments.length; itr++) {

      var arg = $scope.arguments[itr];
      switch (arg.name) {
        case "params":
          arg.value = $scope.params;
          break;
        case "n":
          arg.value = $scope.n;
          break;
        case "sql":
          arg.value = 'paste0("' + getCompleteSQL() + '")';
          break;
      }
      if (!arg.value || (arg.value == ''))
        continue;

      if (!first)
        result += ",\n"
      first = false;

      if ($scope.insertComments && (arg.comment))
        result += "    # " + arg.comment + "\n";
      result += "    " + arg.name + "=" + arg.value;
    }
    result += "\n)";

    return result;
  }
}