// noinspection JSJQueryEfficiency

/**
 * Table Query Params
 */
function classQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    let tableListType = $('.table-list-type.active').data('id');
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        class_id: $('#filter_class_id').val(),
        medium_id: $('#filter_medium_id').val(),
        show_deleted: tableListType,
    };
}

function NotificationUserqueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        roles: $('#roles').val(),
        over_due_fees_roles: $('#over_due_fees_roles').val(),
        type: $('input[name="type"]:checked').val(),
    };
}

function diaryStudentQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        class_section_id: $('#filter_class_section_id').val(),
        session_year_id: $('#session_year_id').val(),
    };
}

function feesQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    let tableListType = $('.table-list-type.active').data('id');
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        session_year_id: $('#filter_session_year_id').val(),
        medium_id: $('#filter_medium_id').val(),
        show_deleted: tableListType,
    };
}


function PayrollSettingsqueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    let tableListType = $('.table-list-type.active').data('id');
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        type: $('#filter_type').val(),
        show_deleted: tableListType,
    };
}

function leaveDetailQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        session_year_id: $('#filter_session_year_id').val(),
        staff_id: $('#filter_staff_id').val(),
        
    };
}

function schoolQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    let tableListType = $('.table-list-type.active').data('id');
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        package_id: $('#filter_package_id').val(),
        show_deleted: tableListType,
    };
}

function ExamClassQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        exam_id: $('#filter_exam_name').val(),
        class_id: $('#filter_class_name').val()
    };
}

function timetableQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        medium_id: $('#filter_medium_id').val()
    };
}

function getExamResult(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        exam_id: $('.result_exam').val(),
        session_year_id: $('#filter_session_year_id').val(),
        class_section_id: $('#filter_class_section_id').val(),
    };
}

function getYearlyExamResult(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        exam_id: $('#filter_exam_id').val(),
        session_year_id: $('#filter_session_year_id').val(),
        class_section_id: $('#filter_class_section_id').val(),
    };
}

function getSubjectWiseExamResult(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        exam_id: $('#filter_subject_wise_exam_id').val(),
        session_year_id: $('#filter_subject_wise_session_year_id').val(),
        class_section_id: $('#filter_subject_wise_class_section_id').val(),
    };
}

function getRankWiseExamResult(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        exam_id: $('#filter_exam_id').val(),
        session_year_id: $('#filter_rank_wise_session_year_id').val(),
        class_section_id: $('#filter_rank_wise_class_section_id').val(),
        subject_id: $('#filter_rank_wise_subject_id').val(),
    };
}

function SubjectQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    let tableListType = $('.table-list-type.active').data('id');
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        medium_id: $('#filter_subject_id').val(),
        show_deleted: tableListType,
    };
}


function ExpenseQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        category_id: $('#filter_category_id').val(),
        session_year_id: $('#filter_session_year_id').val(),
        month: $('#filter_month').val(),
    };
}

function payrollQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        month: $('#month').val(),
        year: $('#year').val(),
    };
}

function payrollListQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        session_year_id: $('#filter_session_year').val(),
    };
}

function leaveQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        session_year_id: $('#session_year_id').val(),
        filter_upcoming: $('#filter_upcoming').val(),
        month_id: $('#filter_month_id').val(),
        user_id: $('#filter_user_id').val(),
    };
}

function AssignTeacherQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        class_id: $('#filter_class_id').val(),
    };
}


function webSettingsQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
    };
}

function StudentDetailQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        class_id: $('#filter_class_section_id').val(),

    };
}


function AssignmentSubmissionQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        subject_id: $('#filter-subject-id').val(),
        class_section_id: $('#filter-class-section-id').val(),
        semester_id: $('#filter-semester-id').val(),
    };
}

function CreateAssignmentSubmissionQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        subject_id: $('#filter-subject-id').val(),
        class_id: $('#filter-class-section-id').val(),
        session_year_id: $("#filter_session_year_id").val(),
        semester_id: $('#filter-semester-id').val(),
    };
}

function CreateLessonQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        class_subject_id: $('#filter-subject-id').val(),
        class_id: $('#filter-class-section-id').val(),
        lesson_id: $('#filter_lesson_id').val(),
        semester_id: $('#filter-semester-id').val(),
        session_year_id: $('#filter_session_year_id').val(),
    };
}

function CreateTopicQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        class_subject_id: $('#filter-subject-id').val(),
        class_id: $('#filter-class-section-id').val(),
        lesson_id: $('#filter-lesson-id').val(),
        semester_id: $('#filter-semester-id').val(),
        session_year_id: $('#filter_session_year_id').val(),
    };
}

function uploadMarksqueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        'class_section_id': $('#class_section_id').val(),
        'class_subject_id': $('#subject_id').val(),
        'exam_id': $('#exam_id').val(),
    };
}

function feesPaidListQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        student_id: $('#student_id').val(),
        session_year_id: $('#session_year_id').val(),
        class_section_id: $('#filter-class-section-id').val(),
    };
}

function optionalFeesPaidListQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        // fees_id: $('#filter_fees_id').val(),
        class_id: $('#filter_fees_id').find('option:selected').data('class-section-id'),
        session_year_id: $('#session_year_id').val(),
        filter_optional_fees: $('#filter_optional_fees').val(),
        class_section_id: $('#filter-class-section-id').val(),
    };
}

function feesPaymentTransactionQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        payment_status: $('#filter_payment_status').val(),
        session_year_id: $('#filter_session_year_id').val(),
        month: $('.paid-month').val(),
    };
}

function subscriptionTransactionQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    let tableListType = $('.table-list-type.active').data('id');
    
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        payment_status: $('#filter_payment_status').val(),
    };
}

function studentRollNumberQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        'class_section_id': $('#filter_roll_number_class_section_id').val(),
        'sort_by': $('#sort_by').val(),
        'order_by': $('#order_by').val(),
    };
}

function onlineExamQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    let tableListType = $('.table-list-type.active').data('id');
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        show_deleted: tableListType,
        'class_section_id': $('#filter-class-section-id').val(),
        'class_subject_id': $('#filter-subject-id').val(),
        'subject_id': $('#filter-class-subject-id').val(),
        'session_year_id': $('#filter_session_year_id').val(),
    };
}


function onlineExamQuestionsQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        'class_section_id': $('#filter-class-section-id').val(),
        'class_subject_id': $('.filter-subject-id').val(),
        'subject_id': $('#filter-class-subject-id').val(),
        'difficulty': $('#filter_difficulty').val(),
    };
}

function studentDetailsQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    let tableListType = $('.table-list-type.active').data('id');
    // var options = $table.bootstrapTable('getOptions');
    // if (!options.pagination) {
    //     p.limit = options.totalRows
    //     // return p;
    // }
    // p.limit = -1;
    var options = $table.bootstrapTable('getOptions');
    if (options.pagination != undefined && !options.pagination) {
        // sample data only contains 20 items - so replace limit = options.totalRows;
        p.limit = options.totalRows;
        // .NET API fails if these params are unset
        // if they = undefined  they are not passed to server
        // for some reason all params must be present when submitted to a .NET Web API
        // even if defined as optional in .NET method - call fails if not present
      }

    var data = {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        class_id: $('#filter_class_section_id').val(),
        session_year_id: $('#filter_session_year_id').val(),
        exam_id: $('#exam_id').val(),
        campus: $('#filter_campus').val(),
        show_deactive: tableListType,
    };

    return data;
}

function attendanceQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        'class_section_id': $('#timetable_class_section').val(),
        'date': $('#date').val(),
    }
}

function holidayQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        'session_year_id': $('#filter_session_year_id').val(),
        'month': $('#filter_month').val(),
    }
}

function galleryQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        'session_year_id': $('#filter_session_year_id').val(),
    }
}

function userStatusQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        role: $('.role').val(),
        class_section_id: $('.class_section_id').val(),
    }
}

function queryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    let tableListType = $('.table-list-type.active').data('id');
    if (tableListType === 1) {
        $('.btn-update-rank').hide();
    } else {
        $('.btn-update-rank').show();
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        show_deleted: tableListType,
    };
}

function diaryQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    let tableListType = $('.table-list-type.active').data('id');
    if (tableListType === 1) {
        $('.btn-update-rank').hide();
    } else {
        $('.btn-update-rank').show();
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        show_deleted: tableListType,
        class_section_id: $('#diary_filter_class_section_id').val(),
        session_year_id: $('#diary_filter_session_year_id').val(),
        filter_diary_type: $('#filter_diary_type').val(),
    };
}


function packageQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    let tableListType = $('.table-list-type.active').data('id');
    if (tableListType === 'Trashed') {
        $('.btn-update-rank').hide();
    } else {
        $('.btn-update-rank').show();
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        type: $('#type').val(),
        show_deleted: tableListType,
    };
}

function promoteStudentQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        'class_section_id': $('#student_class_section').val(),
        'session_year_id': $('#session_year_id').val(),
    };
}

function examQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    let tableListType = $('.table-list-type.active').data('id');
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        session_year_id: $('#filter_session_year_id').val(),
        medium_id: $('#filter_medium_id').val(),
        show_deleted: tableListType,
    };
}

function subscriptionQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
    };
}

function subscriptionReportQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        status: $('#status').val()
    };
}

function examTimetableQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        exam_id: $('#filter-exam-id').val()
    };
}

function announcementQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    let tableListType = $('.table-list-type.active').data('id');
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        show_deleted: tableListType,
        session_year_id: $('#filter_session_year_id').val(),
        class_section_id: $('#filter_class_section_id').val(),
        subject_id: $('#filter_subject_id').val()
    };
}

$("#filter_class_id,#filter_class_section_id,#filter_teacher_id,#filter_subject_id,#filter_medium_id,#filter_subject_id,#filter_campus").on('change', function () {
    $('#table_list').bootstrapTable('refresh');
})


$('#filter-question-class-section-id,#filter-subject-id,#filter-class-section-id').on('change', function () {
    $('#table_list_questions').bootstrapTable('refresh');
})


//Show All / Trashed list Event
$('.table-list-type').on('click', function (e) {
    e.preventDefault();
    //Highlight the current selected type
    $('.table-list-type').removeClass('active').parent("b").contents().unwrap();
    $(this).wrap("<b></b>").addClass('active');

    //Refresh the bootstrap table so that data can be loaded according to the selected type
    //Based on this selected value new query param will be added in Bootstrap Table Query Params
    $('#table_list').bootstrapTable('refresh');
})


// $('.student-list-type').on('click', function (e) {
//     e.preventDefault();
//     //Highlight the current selected type
//     $('.student-list-type').removeClass('active').parent("b").contents().unwrap();
//     $(this).wrap("<b></b>").addClass('active');

//     //Refresh the bootstrap table so that data can be loaded according to the selected type
//     //Based on this selected value new query param will be added in Bootstrap Table Query Params
//     $('#table_list').bootstrapTable('refresh');
// })

function transferStudentQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        'current_class_section': $('#transfer_class_section').val(),
    };
}

function activeDeactiveQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    let tableListType = $('.table-list-type.active').data('id');
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        show_deactive: tableListType,
        session_year_id: $('#filter_session_year_id').val(),
    };
}

function studentsQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        'class_id': $('#filter_class_id').val(),
        'class_section_id': $('#filter_class_section_id').val(),
    }
}

function diaryStudentQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        class_section_id: $('#filter_class_section_id').val(),
        session_year_id: $('#session_year_id').val(),
    };
}


function schoolInquiryQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        'status' : $('#filter_status_id').val(),
        'date' : $('#filter_date').val(),
    };
}

function guardianQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        class_id: $('#filter_class_id').val(),
        class_section_id: $('#filter_class_section_id').val()
    };
}

function FormFieldQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    let tableListType = $('.table-list-type.active').data('id');
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        filter_all_user_type: $('#filter_all_user_type').val(),
        show_deleted: tableListType
    };
}

function certificateTemplateQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
    };
}

function contactInquiryQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    let tableListType = $('.table-list-type.active').data('id');
    return {
        limit: p.limit,
        offset: p.offset,
        search: p.search,
        sort: p.sort,
        order: p.order,
        show_deleted: tableListType
    };
}

function studentReportsQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    let tableListType = $('.table-list-type.active').data('id');
  
    var options = $table.bootstrapTable('getOptions');
    if (options.pagination != undefined && !options.pagination) {
        p.limit = options.totalRows;
      }

    var data = {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        class_id: $('#filter_class_section_id').val(),
        session_year_id: $('#filter_session_year_id').val(),
        exam_id: $('#exam_id').val(),
        show_deactive: tableListType,
    };

    return data;
}

function assignElectiveSubjectQueryParams(p) {
    if (this.pagination !== undefined && !this.pagination) {
        p.limit = this.totalRows;
    } else if (typeof $table !== 'undefined' && $table.bootstrapTable) {
        var _options = $table.bootstrapTable('getOptions');
        if (_options && _options.pagination !== undefined && !_options.pagination) {
            p.limit = _options.totalRows;
        }
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        session_year_id: $('#filter-session-year-id').val(),
        class_section_id: $('#filter-class-section-id').val(),
        elective_subject_group_id: $('#filter-elective-subject-group-id').val(),
        subject_id: $('#subject_id').val(),
    };
}