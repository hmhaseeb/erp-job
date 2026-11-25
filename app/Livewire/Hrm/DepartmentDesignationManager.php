<?php

namespace App\Livewire\Hrm;

use App\Models\Department;
use App\Models\Designation;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Departments & Designations')]
class DepartmentDesignationManager extends Component
{
    public string $dept_name = '';
    public string $dept_code = '';
    public string $dept_desc = '';
    public ?int $editingDeptId = null;

    public string $desig_name = '';
    public ?int $desig_dept_id = null;
    public string $desig_grade = '';
    public ?int $editingDesigId = null;

    public function saveDepartment(): void
    {
        $this->validate([
            'dept_name' => ['required', 'string', 'max:100', 'unique:departments,name'.($this->editingDeptId ? ','.$this->editingDeptId : '')],
            'dept_code' => ['required', 'string', 'max:30', 'unique:departments,code'.($this->editingDeptId ? ','.$this->editingDeptId : '')],
        ]);
        Department::updateOrCreate(
            ['id' => $this->editingDeptId],
            ['name' => $this->dept_name, 'code' => strtoupper($this->dept_code), 'description' => $this->dept_desc, 'status' => 'active']
        );
        $this->reset(['dept_name', 'dept_code', 'dept_desc', 'editingDeptId']);
        $this->dispatch('toast', message: 'Department saved.', type: 'success');
    }

    public function editDepartment(int $id): void
    {
        $d = Department::findOrFail($id);
        $this->editingDeptId = $d->id;
        $this->dept_name = $d->name;
        $this->dept_code = $d->code;
        $this->dept_desc = (string) ($d->description ?? '');
    }

    public function deleteDepartment(int $id): void
    {
        Department::findOrFail($id)->delete();
        $this->dispatch('toast', message: 'Department deleted.', type: 'success');
    }

    public function saveDesignation(): void
    {
        $this->validate([
            'desig_name' => ['required', 'string', 'max:100'],
            'desig_dept_id' => ['nullable', 'exists:departments,id'],
        ]);
        Designation::updateOrCreate(
            ['id' => $this->editingDesigId],
            ['name' => $this->desig_name, 'department_id' => $this->desig_dept_id ?: null, 'grade' => $this->desig_grade, 'status' => 'active']
        );
        $this->reset(['desig_name', 'desig_dept_id', 'desig_grade', 'editingDesigId']);
        $this->dispatch('toast', message: 'Designation saved.', type: 'success');
    }

    public function editDesignation(int $id): void
    {
        $d = Designation::findOrFail($id);
        $this->editingDesigId = $d->id;
        $this->desig_name = $d->name;
        $this->desig_dept_id = $d->department_id;
        $this->desig_grade = (string) ($d->grade ?? '');
    }

    public function deleteDesignation(int $id): void
    {
        Designation::findOrFail($id)->delete();
        $this->dispatch('toast', message: 'Designation deleted.', type: 'success');
    }

    public function render()
    {
        return view('livewire.hrm.department-designation-manager', [
            'departments' => Department::withCount('employees')->orderBy('name')->get(),
            'designations' => Designation::with('department')->withCount('employees')->orderBy('name')->get(),
        ]);
    }
}
