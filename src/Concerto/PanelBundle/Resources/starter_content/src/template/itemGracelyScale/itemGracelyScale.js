testRunner.filterProvider.register('reverse', function () {
  return function (items) {
    return items.slice().reverse();
  };
});

testRunner.compileProvider.component('itemGracelyScale', {
  templateUrl: testRunner.settings.platformUrl + "/ViewTemplate/itemGracelyScale/content?css=1&html=1&js=0",
  bindings: {
    item: '=',
    response: '=',
    responseRequired: '<'
  },
  controller: function controller($scope) {

    $scope.intensity = null;
    $scope.unpleasantness = null;

    this.$onInit = function() {
      $scope.item = this.item;
      $scope.response = this.response;
      $scope.responseRequired = this.responseRequired;

      $scope.unpleasantnessVisible = typeof($scope.item.responseOptions.gracelyScaleShow) === 'undefined' || $scope.item.responseOptions.gracelyScaleShow === 'both' || $scope.item.responseOptions.gracelyScaleShow === 'unpleasantness';
      $scope.intensityVisible = typeof($scope.item.responseOptions.gracelyScaleShow) === 'undefined' || $scope.item.responseOptions.gracelyScaleShow === 'both' || $scope.item.responseOptions.gracelyScaleShow === 'intensity';

      $scope.response.isValid = function() {
        if($scope.unpleasantnessVisible && !$scope.unpleasantness) return false;
        if($scope.intensityVisible && !$scope.intensity) return false;
        return true;
      }

      if($scope.response.value) {
        let pastResponse = JSON.parse($scope.response.value);
        $scope.intensity = pastResponse.intensity;
        $scope.unpleasantness = pastResponse.unpleasantness;
      }

      testRunner.addExtraControl("r" + $scope.item.id, function () {
        return JSON.stringify({
          intensity: $scope.intensity,
          unpleasantness: $scope.unpleasantness
        });
      });
    };

    $scope.intensityOptions = {
      options: [
        {
          value: 0,
          bgColor: '#ddfbdf'
        },
        {
          value: 1,
          bgColor: '#fde7e7'
        },
        {
          value: 2,
          bgColor: '#ffd8d8'
        },
        {
          value: 3,
          bgColor: '#fdc5c2'
        },
        {
          value: 4,
          bgColor: '#ffb6b1'
        },
        {
          value: 5,
          bgColor: '#fba19b'
        },
        {
          value: 6,
          bgColor: '#fd9187'
        },
        {
          value: 7,
          bgColor: '#ff9083'
        },
        {
          value: 8,
          bgColor: '#fd7e6c'
        },
        {
          value: 9,
          bgColor: '#fb6e55'
        },
        {
          value: 10,
          bgColor: '#fd5e3c'
        },
        {
          value: 11,
          bgColor: '#fd4e1b'
        },
        {
          value: 12,
          bgColor: '#fb400a'
        },
        {
          value: 13,
          bgColor: '#f50707'
        },
        {
          value: 14,
          bgColor: '#ec0101'
        },
        {
          value: 15,
          bgColor: '#e20101'
        },
        {
          value: 16,
          bgColor: '#da0000'
        },
        {
          value: 17,
          bgColor: '#d00101'
        },
        {
          value: 18,
          bgColor: '#c30000'
        },
        {
          value: 19,
          bgColor: '#bd0000'
        },
        {
          value: 20,
          bgColor: '#ad0202'
        },
      ],
      labels: [
        {
          text: 'no pain sensation',
          bottom: '0.2%'
        },
        {
          text: 'faint',
          bottom: '5.5%'
        },
        {
          text: 'very weak',
          bottom: '19.7%'
        },
        {
          text: 'weak',
          bottom: '24.8%'
        },
        {
          text: 'very mild',
          bottom: '31.8%'
        },
        {
          text: 'mild',
          bottom: '38.2%'
        },
        {
          text: 'moderate',
          bottom: '52.5%'
        },
        {
          text: 'barely strong',
          bottom: '58.2%'
        },
        {
          text: 'slightly intense',
          bottom: '63%'
        },
        {
          text: 'strong',
          bottom: '67.6%'
        },
        {
          text: 'intense',
          bottom: '76.8%'
        },
        {
          text: 'very intense',
          bottom: '81%'
        },
        {
          text: 'extremely intense',
          bottom: '86.7%'
        },
      ],
    };

    $scope.unpleasantnessOptions = {
      options: [
        {
          value: 0,
          bgColor: '#ddfbdf'
        },
        {
          value: 1,
          bgColor: '#fde7e7'
        },
        {
          value: 2,
          bgColor: '#ffd8d8'
        },
        {
          value: 3,
          bgColor: '#fdc5c2'
        },
        {
          value: 4,
          bgColor: '#ffb6b1'
        },
        {
          value: 5,
          bgColor: '#fba19b'
        },
        {
          value: 6,
          bgColor: '#fd9187'
        },
        {
          value: 7,
          bgColor: '#ff9083'
        },
        {
          value: 8,
          bgColor: '#fd7e6c'
        },
        {
          value: 9,
          bgColor: '#fb6e55'
        },
        {
          value: 10,
          bgColor: '#fd5e3c'
        },
        {
          value: 11,
          bgColor: '#fd4e1b'
        },
        {
          value: 12,
          bgColor: '#fb400a'
        },
        {
          value: 13,
          bgColor: '#f50707'
        },
        {
          value: 14,
          bgColor: '#ec0101'
        },
        {
          value: 15,
          bgColor: '#e20101'
        },
        {
          value: 16,
          bgColor: '#da0000'
        },
        {
          value: 17,
          bgColor: '#d00101'
        },
        {
          value: 18,
          bgColor: '#c30000'
        },
        {
          value: 19,
          bgColor: '#bd0000'
        },
        {
          value: 20,
          bgColor: '#ad0202'
        },
      ],
      labels: [
        {
          text: 'neutral',
          bottom: '0.2%'
        },
        {
          text: 'slightly unpleasant',
          bottom: '24%'
        },
        {
          text: 'slightly annoying',
          bottom: '29%'
        },
        {
          text: 'unpleasant',
          bottom: '34.5%'
        },
        {
          text: 'annoying',
          bottom: '38.5%'
        },
        {
          text: 'slightly distressing',
          bottom: '43.5%'
        },
        {
          text: 'very unpleasant',
          bottom: '48.5%'
        },
        {
          text: 'distressing',
          bottom: '52.5%'
        },
        {
          text: 'very annoying',
          bottom: '55.5%'
        },
        {
          text: 'slightly intolerable',
          bottom: '59.8%'
        },
        {
          text: 'very distressing',
          bottom: '62.8%'
        },
        {
          text: 'intolerable',
          bottom: '75.5%'
        },
        {
          text: 'very intolerable',
          bottom: '82%'
        },
      ],
    };

    $scope.selectIntensity = function (value) {
      $scope.intensity = value;
    };

    $scope.selectUnpleasantness = function (value) {
      $scope.unpleasantness = value;
    };
  }
});
