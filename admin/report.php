<?php $faculty_id = isset($_GET['fid']) ? $_GET['fid'] : ''; ?>
<?php
function ordinal_suffix($num)
{
    $num = $num % 100; // protect against large numbers
    if ($num < 11 || $num > 13) {
        switch ($num % 10) {
            case 1:
                return $num . 'st';
            case 2:
                return $num . 'nd';
            case 3:
                return $num . 'rd';
        }
    }
    return $num . 'th';
}
?>
<div class="col-lg-12">
    <div class="callout callout-info">
        <div class="d-flex w-100 justify-content-center align-items-center">
            <label for="faculty">Select Faculty</label>
            <div class=" mx-2 col-md-4">
                <select name="" id="faculty_id" class="form-control form-control-sm select2">
                    <option value=""></option>
                    <?php
                    $faculty = $conn->query("SELECT *,concat(firstname,' ',lastname) as name FROM faculty_list order by concat(firstname,' ',lastname) asc");
                    $f_arr = array();
                    $fname = array();
                    while ($row = $faculty->fetch_assoc()):
                        $f_arr[$row['id']] = $row;
                        $fname[$row['id']] = ucwords($row['name']);
                        ?>
                        <option value="<?php echo $row['id'] ?>" <?php echo isset($faculty_id) && $faculty_id == $row['id'] ? "selected" : "" ?>>
                            <?php echo ucwords($row['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12 mb-1">
            <div class="d-flex justify-content-end w-100">
                <button class="btn btn-sm btn-success bg-gradient-success" style="display:none" id="print-btn">
                    <i class="fa fa-print"></i> Print
                </button>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3">
            <div class="callout callout-info">
                <div class="list-group" id="class-list">
                    <!-- Dynamically populated via AJAX -->
                </div>
            </div>
        </div>
        <div class="col-md-9">
            <div class="callout callout-info" id="printable">
                <div>
                    <h3 class="text-center">Evaluation Report</h3>
                    <hr>
                    <table width="100%">
                        <tr>
                            <td width="50%">
                                <p><b>Faculty: <span id="fname"></span></b></p>
                            </td>
                            <td width="50%">
                                <p><b>Academic Year:
                                    <span id="ay">
                                        <?php echo $_SESSION['academic']['year'] . ' ' . (ordinal_suffix($_SESSION['academic']['semester'])) ?> Semester
                                    </span></b>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <td width="50%">
                                <p><b>Class: <span id="classField"></span></b></p>
                            </td>
                            <td width="50%">
                                <p><b>Subject: <span id="subjectField"></span></b></p>
                            </td>
                        </tr>
                    </table>
                    <p class=""><b>Total Student Evaluated: <span id="tse"></span></b></p>
                    <div class="mt-2">
                  <b>Grand Overall Rating: <span id="final_overall_total">-</span></b>
                </div>
                </div>
                <fieldset class="border border-info p-2 w-100">
                    <legend class="w-auto">Rating Legend</legend>
                    <p>5 = Strongly Agree, 4 = Agree, 3 = Uncertain, 2 = Disagree, 1 = Strongly Disagree</p>
                </fieldset>

                <?php
                // Fetch criteria
                $criteria = $conn->query("SELECT * FROM criteria_list 
                    WHERE id IN (
                        SELECT criteria_id FROM question_list 
                        WHERE academic_id = {$_SESSION['academic']['id']}
                    ) 
                    ORDER BY ABS(order_by) ASC");
                while ($crow = $criteria->fetch_assoc()):
                ?>
                <table class="table table-condensed wborder" data-criteria-id="<?php echo $crow['id']; ?>">
                    <thead>
                        <tr class="bg-gradient-secondary">
                            <th class="p-1"><b><?php echo $crow['criteria'] ?></b></th>
                            <th width="5%" class="text-center">1</th>
                            <th width="5%" class="text-center">2</th>
                            <th width="5%" class="text-center">3</th>
                            <th width="5%" class="text-center">4</th>
                            <th width="5%" class="text-center">5</th>
                            <th width="10%"class="text-center" >Overall %</th>
                        </tr>
                    </thead>
                    <tbody class="tr-sortable">
                        <?php
                        $questions = $conn->query("SELECT * FROM question_list 
                            WHERE criteria_id = {$crow['id']} 
                              AND academic_id = {$_SESSION['academic']['id']} 
                            ORDER BY ABS(order_by) ASC");
                        while ($row = $questions->fetch_assoc()):
                        ?>
                        <tr class="bg-white question-row" 
                            data-qid="<?php echo $row['id']; ?>" 
                            data-cid="<?php echo $crow['id']; ?>">
                            <td class="p-1" width="40%">
                                <?php echo $row['question'] ?>
                            </td>
                            <?php for ($c = 1; $c <= 5; $c++): ?>
                                <td class="text-center">
                                    <span class="rate_<?php echo $c . '_' . $row['id']; ?> rates"></span>
                                </td>
                            <?php endfor; ?>
                            <td class="text-center overall_<?php echo $row['id']; ?>">-</td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr class="bg-light" style="display: none;">
                            <td class="p-1"><b>Overall Total</b></td>
                            <td colspan="5" class="text-center"><b>Average Rating:</b></td>
                            <!-- UNIQUE SELECTOR FOR THIS CRITERIA -->
                            <td class="text-center overall_total" data-criteria-id="<?php echo $crow['id']; ?>">-</td>
                        </tr>
                    </tfoot>
                </table>
                <?php endwhile; ?>

                <!-- If you want one grand overall average across all criteria, add a row here: -->
                
               
            </div>
        </div>
    </div>
    <style>
        .list-group-item:hover {
            color: black !important;
            font-weight: 700 !important;
        }
    </style>
    <noscript>
        <style>
            table {
                width: 100%;
                border-collapse: collapse;
            }
            table.wborder tr,
            table.wborder td,
            table.wborder th {
                border: 1px solid gray;
                padding: 3px
            }
            table.wborder thead tr {
                background: #6c757d linear-gradient(180deg, #828a91, #6c757d) repeat-x !important;
                color: #fff;
            }
            .text-center {
                text-align: center;
            }
            .text-right {
                text-align: right;
            }
            .text-left {
                text-align: left;
            }
        </style>
    </noscript>
    <script>
        $(document).ready(function () {
            $('#faculty_id').change(function () {
                if ($(this).val() > 0)
                    window.history.pushState({}, null, './index.php?page=report&fid=' + $(this).val());
                load_class()
            })
            if ($('#faculty_id').val() > 0)
                load_class()
        });

        function load_class() {
            start_load()
            var fname = <?php echo json_encode($fname) ?>;
            $('#fname').text(fname[$('#faculty_id').val()])
            $.ajax({
                url: "ajax.php?action=get_class",
                method: 'POST',
                data: { fid: $('#faculty_id').val() },
                error: function (err) {
                    console.log(err)
                    alert_toast("An error occured", 'error')
                    end_load()
                },
                success: function (resp) {
                    if (resp) {
                        resp = JSON.parse(resp)
                        if (Object.keys(resp).length <= 0) {
                            $('#class-list').html(
                                '<a href="javascript:void(0)" class="list-group-item list-group-item-action disabled">No data to be display.</a>'
                            )
                        } else {
                            $('#class-list').html('')
                            Object.keys(resp).map(k => {
                                $('#class-list').append(
                                    '<a href="javascript:void(0)" data-json=\'' + JSON.stringify(resp[k]) + '\' data-id="' 
                                    + resp[k].id + '" class="list-group-item list-group-item-action show-result">'
                                    + resp[k].class + ' - ' + resp[k].subj + '</a>'
                                )
                            })

                        }
                    }
                },
                complete: function () {
                    end_load()
                    anchor_func()
                    if ('<?php echo isset($_GET['rid']) ?>' == 1) {
                        $('.show-result[data-id="<?php echo isset($_GET['rid']) ? $_GET['rid'] : '' ?>"]').trigger('click')
                    } else {
                        $('.show-result').first().trigger('click')
                    }
                }
            })
        }

        function anchor_func() {
            $('.show-result').click(function () {
                var vars = [], hash;
                var data = $(this).attr('data-json')
                data = JSON.parse(data)
                var _href = location.href.slice(window.location.href.indexOf('?') + 1).split('&');
                for (var i = 0; i < _href.length; i++) {
                    hash = _href[i].split('=');
                    vars[hash[0]] = hash[1];
                }
                window.history.pushState({}, null, './index.php?page=report&fid=' + vars.fid + '&rid=' + data.id);
                load_report(vars.fid, data.sid, data.id);
                $('#subjectField').text(data.subj)
                $('#classField').text(data.class)
                $('.show-result.active').removeClass('active')
                $(this).addClass('active')
            })
        }

        function load_report(faculty_id, subject_id, class_id) {
            start_load();
            $.ajax({
                url: 'ajax.php?action=get_report',
                method: "POST",
                data: { faculty_id: faculty_id, subject_id: subject_id, class_id: class_id },
                success: function (resp) {
                    if (resp) {
                        resp = JSON.parse(resp);

                        // If no data returned, reset to dashes
                        if (Object.keys(resp).length <= 0) {
                            $('.rates, .overall_category, .overall_total').text('-');
                            $('#tse').text('');
                            $('#print-btn').hide();
                        } else {
                            $('#print-btn').show();
                            $('#tse').text(resp.tse);
                            $('.rates').text('-');  // Reset all question cells

                            var data = resp.data;

                            // We'll store the sum of question averages per criterion
                            var criteriaTotals = {};

                            // For each question in 'data'
                            Object.keys(data).map(q => {
                                var totalSum = 0;
                                var totalResponses = 0;

                                // Fill the per-rating % cells
                                Object.keys(data[q]).map(r => {
                                    var percentage = data[q][r] || 0;
                                    $('.rate_' + r + '_' + q).text(percentage + '%');
                                    totalSum += parseFloat(percentage) * parseInt(r);
                                    totalResponses += parseFloat(percentage);
                                });

                                // Compute the question-level average
                                var avgPercentage = (totalResponses > 0) 
                                    ? (totalSum / totalResponses).toFixed(2) 
                                    : '-';
                                $('.overall_' + q).text(avgPercentage + '%');

                                // Identify the criterion for this question
                                var cId = $('.question-row[data-qid="'+ q +'"]').attr('data-cid');

                                // Track sums in our criteriaTotals object
                                if (!criteriaTotals[cId]) {
                                    criteriaTotals[cId] = { sum: 0, count: 0 };
                                }
                                if (avgPercentage !== '-') {
                                    criteriaTotals[cId].sum += parseFloat(avgPercentage);
                                    criteriaTotals[cId].count++;
                                }
                            });

                            // Now compute the overall average per criterion
                            Object.keys(criteriaTotals).map(cId => {
                                var finalAvg = '-';
                                if (criteriaTotals[cId].count > 0) {
                                    finalAvg = (criteriaTotals[cId].sum / criteriaTotals[cId].count).toFixed(2) + '%';
                                }
                                // Update the cell in the table foot for this criterion
                                $('.overall_total[data-criteria-id="' + cId + '"]').text(finalAvg);
                            });

                            // (Optional) If you want one "grand overall" for ALL criteria, do it here:
                            
                            var grandSum = 0, grandCount = 0;
                            Object.keys(criteriaTotals).map(cId => {
                                if (criteriaTotals[cId].count > 0) {
                                    // average for that criterion
                                    var cAvg = criteriaTotals[cId].sum / criteriaTotals[cId].count;
                                    grandSum += cAvg;
                                    grandCount++;
                                }
                            });
                            var finalOverallAvg = (grandCount > 0) 
                                ? (grandSum / grandCount).toFixed(2) + '%' 
                                : '-';
                            $('#final_overall_total').text(finalOverallAvg);
                        }
                    }
                },
                complete: function () {
                    end_load();
                }
            });
        }

        $('#print-btn').click(function () {
    start_load();

    // Clone the entire printable area
    var printContent = $('#printable').clone();

    // Remove the Rating Legend fieldset if present
    printContent.find('fieldset').remove();

    // For each criteria table (identified by a data-criteria-id attribute),
    // extract the criteria name and overall average rating, then replace
    // the table with a flex container showing them side by side.
    printContent.find('table[data-criteria-id]').each(function(){
        // Get criteria name from the first header cell
        var criteriaName = $(this).find('thead th:first').text().trim();
        // Get overall average rating from the footer cell with class "overall_total"
        var overallRating = $(this).find('tfoot .overall_total').text().trim();

        // Create a flex container with two spans:
        // one for the criteria name and one for the overall rating
        var newElem = $('<div>').css({
            'display': 'flex',
            'justify-content': 'space-between',
            'font-size': '16px',
            'margin': '3px 0'
        });
        newElem.append($('<span>').text(criteriaName));
        newElem.append($('<span>').text(': ' + overallRating));

        // Replace the original table with this new element
        $(this).replaceWith(newElem);
    });

    // Open a new window, write the final HTML, and print it
    var nw = window.open("Report", "_blank", "width=900,height=700");
    nw.document.write(printContent.html());
    nw.document.close();
    nw.print();

    // Close the new window after a short delay
    setTimeout(function () {
        nw.close();
        end_load();
    }, 750);
});


    </script>
</div>
