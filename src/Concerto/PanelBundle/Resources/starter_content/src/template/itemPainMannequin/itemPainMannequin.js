testRunner.compileProvider.component('itemPainMannequin', {
  templateUrl: testRunner.settings.platformUrl + "/ViewTemplate/itemPainMannequin/content?css=1&html=1&js=0",
  bindings: {
    item: '=',
    response: '=',
    responseRequired: '<'
  },
  controller: function controller($scope, $timeout) {
    $scope.bodyParts = [
      {
        id: 1,
        label: 'head right f'
      },
      {
        id: 2,
        label: 'head left f'
      },
      {
        id: 3,
        label: 'face right'
      },
      {
        id: 4,
        label: 'face left'
      },
      {
        id: 5,
        label: 'neck right'
      },
      {
        id: 6,
        label: 'neck left'
      },
      {
        id: 7,
        label: 'right shoulder f'
      },
      {
        id: 8,
        label: 'right chest'
      },
      {
        id: 9,
        label: 'left chest'
      },
      {
        id: 10,
        label: 'left shoulder f'
      },
      {
        id: 11,
        label: 'right arm f'
      },
      {
        id: 12,
        label: 'right elbow joint'
      },
      {
        id: 13,
        label: 'right forearm f'
      },
      {
        id: 14,
        label: 'right wrist f'
      },
      {
        id: 15,
        label: 'right hand f'
      },
      {
        id: 16,
        label: 'left arm f'
      },
      {
        id: 17,
        label: 'left elbow joint'
      },
      {
        id: 18,
        label: 'left forearm f'
      },
      {
        id: 19,
        label: 'left wrist f'
      },
      {
        id: 20,
        label: 'left hand f'
      },
      {
        id: 21,
        label: 'abdomen right'
      },
      {
        id: 22,
        label: 'abdomen left'
      },
      {
        id: 23,
        label: 'pelvis right'
      },
      {
        id: 24,
        label: 'pelvis left'
      },
      {
        id: 25,
        label: 'right hip f'
      },
      {
        id: 26,
        label: 'right groin'
      },
      {
        id: 27,
        label: 'left groin'
      },
      {
        id: 28,
        label: 'left hip f'
      },
      {
        id: 29,
        label: 'right thigh'
      },
      {
        id: 30,
        label: 'left thigh'
      },
      {
        id: 31,
        label: 'right knee'
      },
      {
        id: 32,
        label: 'left knee'
      },
      {
        id: 33,
        label: 'right shin'
      },
      {
        id: 34,
        label: 'left shin'
      },
      {
        id: 35,
        label: 'right ankle'
      },
      {
        id: 36,
        label: 'left ankle'
      },
      {
        id: 37,
        label: 'right foot'
      },
      {
        id: 38,
        label: 'left foot'
      },
      {
        id: 39,
        label: 'head left'
      },
      {
        id: 40,
        label: 'head right'
      },
      {
        id: 41,
        label: 'occiput left'
      },
      {
        id: 42,
        label: 'occiput right'
      },
      {
        id: 43,
        label: 'nape left'
      },
      {
        id: 44,
        label: 'nape right'
      },
      {
        id: 45,
        label: 'left shoulder b'
      },
      {
        id: 46,
        label: 'left back'
      },
      {
        id: 47,
        label: 'right back'
      },
      {
        id: 48,
        label: 'right shoulder b'
      },
      {
        id: 49,
        label: 'left arm b'
      },
      {
        id: 50,
        label: 'left dorsal'
      },
      {
        id: 51,
        label: 'right dorsal'
      },
      {
        id: 52,
        label: 'right arm b'
      },
      {
        id: 53,
        label: 'left elbow'
      },
      {
        id: 54,
        label: 'left forearm b'
      },
      {
        id: 55,
        label: 'left wrist b'
      },
      {
        id: 56,
        label: 'left hand b'
      },
      {
        id: 57,
        label: 'right elbow'
      },
      {
        id: 58,
        label: 'right forearm b'
      },
      {
        id: 59,
        label: 'right wrist b'
      },
      {
        id: 60,
        label: 'right hand b'
      },
      {
        id: 61,
        label: 'left lumbar'
      },
      {
        id: 62,
        label: 'right lumbar'
      },
      {
        id: 63,
        label: 'left hip b'
      },
      {
        id: 64,
        label: 'left buttock'
      },
      {
        id: 65,
        label: 'right buttock'
      },
      {
        id: 66,
        label: 'right hip b'
      },
      {
        id: 67,
        label: 'left thigh b'
      },
      {
        id: 68,
        label: 'right thigh b'
      },
      {
        id: 69,
        label: 'left knee joint'
      },
      {
        id: 70,
        label: 'right knee joint'
      },
      {
        id: 71,
        label: 'left calf'
      },
      {
        id: 72,
        label: 'right calf'
      },
      {
        id: 73,
        label: 'left ankle b'
      },
      {
        id: 74,
        label: 'right ankle b'
      },
      {
        id: 75,
        label: 'left heel'
      },
      {
        id: 76,
        label: 'right heel'
      },
    ];

    $scope.activeMark = null;

    let timeout;

    this.$onInit = function() {
      $scope.item = this.item;
      $scope.response = this.response;
      $scope.responseRequired = this.responseRequired;
      
      $scope.response.isValid = function() {
        return this.value.reportFront.length > 0 || this.value.reportBack.length > 0;
      }

      $scope.allowAreaMultiMarks = $scope.item.responseOptions.painMannequinAreaMultiMarks == 1;

      if($scope.response.value) {
        if(typeof $scope.response.value === 'string' || $scope.response.value instanceof String) {
          $scope.response.value = JSON.parse($scope.response.value);
        }
      } else {
        $scope.response.value = {
          reportFront: [],
          reportBack: []
        }
      }

      $scope.gender = $scope.item.responseOptions.painMannequinGender;
      if($scope.gender == "custom") {
        $scope.gender = $scope.item.responseOptions.painMannequinGenderValue;
      }

      loadData();

      testRunner.addExtraControl("r" + $scope.item.id, function () {
        return JSON.stringify($scope.response.value);
      });
    };

    function loadData() {
      if ($(".i" + $scope.item.id + "-" + $scope.gender + "-svg path").length > 0) {
        setEvents();
        if(!$scope.allowAreaMultiMarks) {
          for(let i=0;i<$scope.response.value.reportFront.length;i++) {
            $scope.onIntensityChange($scope.response.value.reportFront[i]);
          }
          for(let i=0;i<$scope.response.value.reportBack.length;i++) {
            $scope.onIntensityChange($scope.response.value.reportBack[i]);
          }
        }
      } else {
        timeout = $timeout(loadData, 100);
      }
    }

    function setEvents() {
      $('.i' + $scope.item.id + '-pain-mannequin path')
        .on('click', function (e) {
        let parent = $(e.target).parents(".pain-mannequin-svg");
        let relX = e.pageX - parent.offset().left;
        let relY = e.pageY - parent.offset().top;
        let source;
        if (parent.parent().hasClass('pain-mannequin-front')) {
          source = 'front';
        } else if (parent.parent().hasClass('pain-mannequin-back')) {
          source = 'back';
        }
        addNote(+new Date(), relY - 8, relX - 8, e.target.id, source);
        $(this).addClass("highlighted");
      })
        .hover(
        function () {
          $(this).addClass("hovered");
        },
        function () {
          $(this).removeClass("hovered");
        }
      );
    }

    function noteExist(area, source) {
      if(getMarkName(area, source) !== null) return true;
      else return false;
    }

    function getMarkName(area, source) {
      let arr = source === "front" ? $scope.response.value.reportFront : $scope.response.value.reportBack;
      for(let i=0;i<arr.length;i++) {
        if(arr[i].area === area) return source + '-' + (i+1);
      }
      return null;
    }

    function addNote(timeStamp, top, left, id, source) {
      let area;
      for (let i = 0; i < $scope.bodyParts.length; i++) {
        if ("i" + $scope.item.id + "-" + $scope.bodyParts[i].id === id) {
          area = $scope.bodyParts[i].label;
          break;
        }
      }

      let mark = {
        area: area,
        description: '',
        id: id.replace('i' + $scope.item.id + '-', ''),
        intensity: 50,
        size: $scope.allowAreaMultiMarks ? 50 : null,
        x: left,
        y: top,
      };

      if(!$scope.allowAreaMultiMarks && noteExist(area, source)) {
        $scope.activateMark(getMarkName(area, source));
        $scope.$apply();
        return;
      }

      if (source === 'front') {
        $scope.response.value.reportFront.push(mark);
        $scope.activeMark = 'front-' + $scope.response.value.reportFront.length;
      } else if (source === 'back') {
        $scope.response.value.reportBack.push(mark);
        $scope.activeMark = 'back-' + $scope.response.value.reportBack.length;
      }

      $scope.onIntensityChange(mark);
      $scope.$apply();

      $('.mark-content').draggable({handle: '.mark-handle', containment: 'body'});
    }

    $scope.onIntensityChange = function(mark) {
      if(!$scope.allowAreaMultiMarks) {
        let elem = $("#i"+$scope.item.id+"-"+mark.id);
        elem.css({"cssText":"fill-opacity: 1.0 !important; fill: " + $scope.getBgColor(mark.intensity)});
      }
    }

    $scope.getBgColor = function (intensity) {
      let bgColor;
      if (intensity <= 10) {
        bgColor = '#fcbbbc';
      } else if (intensity <= 20) {
        bgColor = '#ffa4a4';
      } else if (intensity <= 30) {
        bgColor = '#ff8e8b';
      } else if (intensity <= 40) {
        bgColor = '#ff746d';
      } else if (intensity <= 50) {
        bgColor = '#ff4843';
      } else if (intensity <= 60) {
        bgColor = '#fc3511';
      } else if (intensity <= 70) {
        bgColor = '#ff0000';
      } else if (intensity <= 80) {
        bgColor = '#f80000';
      } else if (intensity <= 90) {
        bgColor = '#e10000';
      } else {
        bgColor = '#d40202';
      }
      return bgColor;
    };

    $scope.activateMark = function (mark) {
      $scope.activeMark = $scope.activeMark !== mark ? mark : null;
    };

    $scope.hideMark = function () {
      $scope.activeMark = null;
    };

    $scope.deleteMark = function (markIndex, source) {
      $scope.activeMark = null;
      let id, hasSiblings;
      if (source === 'front') {
        id = $scope.response.value.reportFront[markIndex].id;
        $scope.response.value.reportFront.splice(markIndex, 1);
        for (let i = 0; i < $scope.response.value.reportFront.length; i++) {
          if ($scope.response.value.reportFront[i].id === id) {
            hasSiblings = true;
            break;
          }
        }
      } else if (source === 'back') {
        id = $scope.response.value.reportBack[markIndex].id;
        $scope.response.value.reportBack.splice(markIndex, 1);
        for (let i = 0; i < $scope.response.value.reportBack.length; i++) {
          if ($scope.response.value.reportBack[i].id === id) {
            hasSiblings = true;
            break;
          }
        }
      }

      if (!hasSiblings) {
        $("#i" + $scope.item.id + '-' + id).removeClass('highlighted')
          .css({"cssText":""});
      }
    };
  }
});
