# Necessary imports. Provides library functions to ease writing tests.
from lib import prebuild, testcase, SUBMITTY_INSTALL_DIR

import subprocess
import os
from pathlib import Path
import shutil


############################################################################
# COPY THE ASSIGNMENT FROM THE SAMPLE ASSIGNMENTS DIRECTORIES

SAMPLE_ASSIGNMENT_CONFIG = SUBMITTY_INSTALL_DIR + "/more_autograding_examples/input_output_subdirectories/config"
SAMPLE_SUBMISSIONS       = SUBMITTY_INSTALL_DIR + "/more_autograding_examples/input_output_subdirectories/submissions"

@prebuild
def initialize(test):
    try:
        os.mkdir(os.path.join(test.testcase_path, "assignment_config"))
    except OSError:
        pass
    try:
        data_path = os.path.join(test.testcase_path, "data")
        if os.path.isdir(data_path):
            shutil.rmtree(data_path)
        os.mkdir(data_path)
    except OSError:
        pass

    subprocess.call(["cp",
        os.path.join(SAMPLE_ASSIGNMENT_CONFIG, "config.json"),
        os.path.join(test.testcase_path, "assignment_config")])

    subprocess.call(["cp"] + ["-r"] +
        Path(SAMPLE_ASSIGNMENT_CONFIG, "test_input").glob( "*") +
        [data_path])

    subprocess.call(["cp"] + ["-r"] +
        Path(SAMPLE_ASSIGNMENT_CONFIG, "test_output").glob("*") +
        [data_path])

    
############################################################################
def cleanup(test):
    subprocess.call(["rm"] + ["-rf"] +
                    Path(test.testcase_path).glob("test*"))
    subprocess.call(["rm"] + ["-f"] +
                    Path(test.testcase_path).glob("results*"))
    subprocess.call(["rm"] + ["-f"] +
                    Path(test.testcase_path, "data").glob("*.cpp"))


@testcase
def correct(test):
    cleanup(test)
    subprocess.call(["cp",
        os.path.join(SAMPLE_SUBMISSIONS, "correct.cpp"),
        os.path.join(test.testcase_path, "data/code.cpp")])

    test.run_compile()
    test.run_run()
    test.run_validator()

    test.diff("grade.txt","grade.txt_correct","-b")
    test.json_diff("results.json","results.json_correct")


@testcase
def buggy(test):
    cleanup(test)
    subprocess.call(["cp",
        os.path.join(SAMPLE_SUBMISSIONS, "buggy.cpp"),
        os.path.join(test.testcase_path, "data/code.cpp")])

    test.run_compile()
    test.run_run()
    test.run_validator()

    test.diff("grade.txt","grade.txt_buggy","-b")
    test.json_diff("results.json","results.json_buggy")

