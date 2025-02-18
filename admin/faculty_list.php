<?php include 'db_connect.php'; ?>
<div class="col-lg-12">
    <div class="card card-outline card-success">
        <div class="card-header">
            <div class="card-tools">
                <a class="btn btn-block btn-sm btn-default btn-flat border-primary" href="./index.php?page=new_faculty">
                    <i class="fa fa-plus"></i> Add New Faculty
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
                            <th>Average Rating</th> <!-- New column for Average Rating -->
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        $qry = $conn->query("
                            SELECT 
                                f.id, 
                                f.school_id, 
                                CONCAT(f.firstname, ' ', f.lastname) AS name, 
                                f.email, 
                                IFNULL(ROUND(AVG(ea.rate), 2), 0) AS average_rating 
                            FROM faculty_list f
                            LEFT JOIN evaluation_list el ON el.faculty_id = f.id
                            LEFT JOIN evaluation_answers ea ON ea.evaluation_id = el.evaluation_id
                            GROUP BY f.id, f.school_id, f.firstname, f.lastname, f.email
                            ORDER BY name ASC
                        ");

                        while ($row = $qry->fetch_assoc()):
                        ?>
                        <tr>
                            <th class="text-center"><?php echo $i++ ?></th>
                            <td><b><?php echo $row['school_id'] ?></b></td>
                            <td><b><?php echo ucwords($row['name']) ?></b></td>
                            <td><b><?php echo $row['email'] ?></b></td>
                            <td><b><?php echo number_format($row['average_rating'], 2) ?></b></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-default btn-sm btn-flat border-info wave-effect text-info dropdown-toggle" data-toggle="dropdown" aria-expanded="true">
                                    Action
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item view_faculty" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">View</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="./index.php?page=edit_faculty&id=<?php echo $row['id'] ?>">Edit</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item delete_faculty" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">Delete</a>
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
        $('.view_faculty').click(function(){
            uni_modal("<i class='fa fa-id-card'></i> Faculty Details", "<?php echo $_SESSION['login_view_folder'] ?>view_faculty.php?id=" + $(this).attr('data-id'));
        });

        $('.delete_faculty').click(function(){
            _conf("Are you sure to delete this faculty?", "delete_faculty", [$(this).attr('data-id')]);
        });

        // Initialize DataTable with responsive support
        $('#list').DataTable({
            "responsive": true,
            "autoWidth": false,
            "pageLength": 10
        });
    });

    function delete_faculty(id) {
        start_load();
        $.ajax({
            url: 'ajax.php?action=delete_faculty',
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
