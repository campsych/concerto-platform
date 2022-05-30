#!/bin/bash

URL=$1;

PATH=${URL#*://*/};
if [[ $PATH == "" ]]; then
  PATH=/;
fi
if [[ $PATH != /* ]]; then
  PATH="/${PATH}";
fi
if [[ $PATH != */ ]]; then
  PATH="${PATH}/";
fi
echo $PATH;