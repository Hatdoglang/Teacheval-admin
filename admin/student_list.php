<?php include 'db_connect.php'; ?>
<div class="col-lg-12">
    <div class="card card-outline card-success">
        <div class="card-header">
            <div class="card-tools">
                <a class="btn btn-block btn-sm btn-default btn-flat border-primary" href="./index.php?page=new_student">
                    <i class="fa fa-plus"></i> Add New Student
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive"> <!-- Keeps Table Responsive -->
                <table class="table table-hover table-bordered" id="list">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th>School ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Current Class</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        $class = array();
                        $classes = $conn->query("SELECT id, concat(curriculum,' ',level,' - ',section) as `class` FROM class_list");
                        while ($row = $classes->fetch_assoc()) {
                            $class[$row['id']] = $row['class'];
                        }
                        $qry = $conn->query("SELECT *, concat(firstname,' ',lastname) as name FROM student_list ORDER BY name ASC");
                        while ($row = $qry->fetch_assoc()):
                        ?>
                        <tr>
                            <th class="text-center"><?php echo $i++ ?></th>
                            <td><b><?php echo $row['school_id'] ?></b></td>
                            <td><b><?php echo ucwords($row['name']) ?></b></td>
                            <td><b><?php echo $row['email'] ?></b></td>
                            <td><b><?php echo isset($class[$row['class_id']]) ? $class[$row['class_id']] : "N/A" ?></b></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-default btn-sm btn-flat border-info wave-effect text-info dropdown-toggle" data-toggle="dropdown" aria-expanded="true">
                                    Action
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item view_student" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">View</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="./index.php?page=edit_student&id=<?php echo $row['id'] ?>">Edit</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item delete_student" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">Delete</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div> <!-- End table-responsive -->
        </div>
    </div>
</div>

<!-- jQuery & Bootstrap Bundle -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>

<script>
    $(document).ready(function(){
        $('.view_student').click(function(){
            uni_modal("<i class='fa fa-id-card'></i> Student Details", "<?php echo $_SESSION['login_view_folder'] ?>view_student.php?id=" + $(this).attr('data-id'));
        });

        $('.delete_student').click(function(){
            _conf("Are you sure to delete this student?", "delete_student", [$(this).attr('data-id')]);
        });

        // Initialize DataTable with responsive support
        $('#list').DataTable({
            "responsive": true,
            "autoWidth": false,
            "pageLength": 10
        });
    });

    function delete_student(id) {
        start_load();
        $.ajax({
            url: 'ajax.php?action=delete_student',
            method: 'POST',
            data: {id: id},
            success: function(resp) {
                if (resp == 1) {
                    alert_toast("Data successfully deleted", 'success');
                    setTimeout(function(){
                        location.reload();
                    }, 1500);
                }
            }
        });
    }
</script>
