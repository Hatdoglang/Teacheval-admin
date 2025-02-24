<?php
include '../db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Restriction</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        @media (max-width: 768px) {
            .table-responsive {
                overflow-x: auto;
            }
            table {
                width: 100%;
                display: block;
            }
            th, td {
                white-space: nowrap;
                text-align: center;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid mt-3">
    <form action="" id="manage-restriction">
        <div class="row">
            <!-- Left Form Section -->
            <div class="col-md-4 border-right">
                <input type="hidden" name="academic_id" value="<?php echo isset($_GET['id']) ? $_GET['id'] : '' ?>">
                <div id="msg" class="form-group"></div>
                
                <!-- Faculty Selection -->
                <div class="form-group mb-2">
                    <label class="form-label">Faculty</label>
                    <select id="faculty_id" class="form-control form-control-sm">
                        <option value=""></option>
                        <?php 
                        $faculty = $conn->query("SELECT *,concat(firstname,' ',lastname) as name FROM faculty_list ORDER BY name ASC");
                        $f_arr = array();
                        while($row = $faculty->fetch_assoc()):
                            $f_arr[$row['id']] = $row;
                        ?>
                        <option value="<?php echo $row['id'] ?>"><?php echo ucwords($row['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Class Selection -->
                <div class="form-group mb-2">
                    <label class="form-label">Class</label>
                    <select id="class_id" class="form-control form-control-sm">
                        <option value=""></option>
                        <?php 
                        $classes = $conn->query("SELECT id,concat(curriculum,' ',level,' - ',section) as class FROM class_list");
                        $c_arr = array();
                        while($row = $classes->fetch_assoc()):
                            $c_arr[$row['id']] = $row;
                        ?>
                        <option value="<?php echo $row['id'] ?>"><?php echo $row['class'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Subject Selection -->
                <div class="form-group mb-2">
                    <label class="form-label">Subject</label>
                    <select id="subject_id" class="form-control form-control-sm">
                        <option value=""></option>
                        <?php 
                        $subject = $conn->query("SELECT id,concat(code,' - ',subject) as subj FROM subject_list");
                        $s_arr = array();
                        while($row = $subject->fetch_assoc()):
                            $s_arr[$row['id']] = $row;
                        ?>
                        <option value="<?php echo $row['id'] ?>"><?php echo $row['subj'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Add Button -->
                <div class="form-group text-center">
                    <button class="btn btn-sm btn-primary" id="add_to_list" type="button">
                        Add to List
                    </button>
                </div>
            </div>

            <!-- Right Table Section -->
            <div class="col-md-8">
                <div class="table-responsive">
                    <table class="table" id="r-list">
                        <thead>
                            <tr>
                                <th>Faculty</th>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $restriction = $conn->query("SELECT * FROM restriction_list WHERE academic_id = {$_GET['id']} ORDER BY id ASC");
                            while($row = $restriction->fetch_assoc()):
                            ?>
                            <tr>
                                <td>
                                    <b><?php echo isset($f_arr[$row['faculty_id']]) ? $f_arr[$row['faculty_id']]['name'] : '' ?></b>
                                    <input type="hidden" name="faculty_id[]" value="<?php echo $row['faculty_id'] ?>">
                                </td>
                                <td>
                                    <b><?php echo isset($c_arr[$row['class_id']]) ? $c_arr[$row['class_id']]['class'] : '' ?></b>
                                    <input type="hidden" name="class_id[]" value="<?php echo $row['class_id'] ?>">
                                </td>
                                <td>
                                    <b><?php echo isset($s_arr[$row['subject_id']]) ? $s_arr[$row['subject_id']]['subj'] : '' ?></b>
                                    <input type="hidden" name="subject_id[]" value="<?php echo $row['subject_id'] ?>">
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-danger" onclick="$(this).closest('tr').remove()" type="button">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    $(document).ready(function(){
        $('#add_to_list').click(function(){
            let facultyId = $('#faculty_id').val();
            let classId = $('#class_id').val();
            let subjectId = $('#subject_id').val();
            
            if (!facultyId || !classId || !subjectId) {
                alert("Please select all fields before adding.");
                return;
            }

            let facultyName = $("#faculty_id option:selected").text();
            let className = $("#class_id option:selected").text();
            let subjectName = $("#subject_id option:selected").text();

            let newRow = `<tr>
                <td><b>${facultyName}</b><input type="hidden" name="faculty_id[]" value="${facultyId}"></td>
                <td><b>${className}</b><input type="hidden" name="class_id[]" value="${classId}"></td>
                <td><b>${subjectName}</b><input type="hidden" name="subject_id[]" value="${subjectId}"></td>
                <td class="text-center">
                    <button class="btn btn-sm btn-danger" onclick="$(this).closest('tr').remove()" type="button">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>`;

            $('#r-list tbody').append(newRow);
            $('#faculty_id').val('');
            $('#class_id').val('');
            $('#subject_id').val('');
        });
    });
</script>
</body>
</html>
