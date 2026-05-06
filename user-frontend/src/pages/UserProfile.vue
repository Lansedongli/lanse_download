<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { useAuthStore } from '@/stores/auth'
import { getUserProfile, updateUserProfile, changePassword } from '@/api/user'

const authStore = useAuthStore()

const formRef = ref(null)
const passwordFormRef = ref(null)
const loading = ref(false)
const passwordLoading = ref(false)

const form = reactive({
  nickname: '',
  email: '',
  phone: '',
  qq: '',
  real_name: '',
})

const passwordForm = reactive({
  old_password: '',
  new_password: '',
  confirm_password: '',
})

const rules = {
  email: [
    { type: 'email', message: '请输入正确的邮箱地址', trigger: 'blur' },
  ],
  phone: [
    { pattern: /^1[3-9]\d{9}$/, message: '请输入正确的手机号码', trigger: 'blur' },
  ],
  qq: [
    { pattern: /^\d{5,15}$/, message: '请输入正确的QQ号', trigger: 'blur' },
  ],
}

const passwordRules = {
  old_password: [
    { required: true, message: '请输入旧密码', trigger: 'blur' },
    { min: 6, max: 30, message: '密码长度在 6 到 30 个字符', trigger: 'blur' },
  ],
  new_password: [
    { required: true, message: '请输入新密码', trigger: 'blur' },
    { min: 6, max: 30, message: '密码长度在 6 到 30 个字符', trigger: 'blur' },
  ],
  confirm_password: [
    { required: true, message: '请确认新密码', trigger: 'blur' },
    {
      validator: (rule, value, callback) => {
        if (value !== passwordForm.new_password) {
          callback(new Error('两次输入的密码不一致'))
        } else {
          callback()
        }
      },
      trigger: 'blur',
    },
  ],
}

onMounted(async () => {
  try {
    const res = await getUserProfile()
    const data = res.user || res.data || res
    form.nickname = data.nickname || ''
    form.email = data.email || ''
    form.phone = data.phone || ''
    form.qq = data.qq || ''
    form.real_name = data.real_name || ''
  } catch (e) {
    // 使用本地缓存数据回填
    const user = authStore.user
    if (user) {
      form.nickname = user.nickname || ''
      form.email = user.email || ''
      form.phone = user.phone || ''
      form.qq = user.qq || ''
      form.real_name = user.real_name || ''
    }
  }
})

async function handleSubmit() {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return

  loading.value = true
  try {
    await updateUserProfile({
      nickname: form.nickname,
      email: form.email,
      phone: form.phone,
      qq: form.qq,
      real_name: form.real_name,
    })
    ElMessage.success('个人信息修改成功')
    // 刷新 store
    await authStore.fetchProfile()
  } catch (e) {
    // 错误已在拦截器中处理
  } finally {
    loading.value = false
  }
}

async function handlePasswordChange() {
  const valid = await passwordFormRef.value.validate().catch(() => false)
  if (!valid) return

  passwordLoading.value = true
  try {
    await changePassword(passwordForm.old_password, passwordForm.new_password)
    ElMessage.success('密码修改成功')
    // 清空密码表单
    passwordForm.old_password = ''
    passwordForm.new_password = ''
    passwordForm.confirm_password = ''
    passwordFormRef.value.resetFields()
  } catch (e) {
    // 错误已在拦截器中处理
  } finally {
    passwordLoading.value = false
  }
}
</script>

<template>
  <div class="profile-page">
    <div class="page-header">
      <h2>个人信息</h2>
      <p class="page-desc">完善您的个人资料，以便获得更好的服务体验</p>
    </div>

    <!-- 基本信息表单 -->
    <el-form
      ref="formRef"
      :model="form"
      :rules="rules"
      label-width="90px"
      label-position="left"
      class="profile-form"
    >
      <el-form-item label="昵称" prop="nickname">
        <el-input
          v-model="form.nickname"
          placeholder="请输入昵称"
          maxlength="30"
          clearable
        />
      </el-form-item>

      <el-form-item label="邮箱" prop="email">
        <el-input
          v-model="form.email"
          placeholder="请输入邮箱地址"
          maxlength="50"
          clearable
        />
      </el-form-item>

      <el-form-item label="手机号" prop="phone">
        <el-input
          v-model="form.phone"
          placeholder="请输入手机号码"
          maxlength="11"
          clearable
        />
      </el-form-item>

      <el-form-item label="QQ" prop="qq">
        <el-input
          v-model="form.qq"
          placeholder="请输入QQ号"
          maxlength="15"
          clearable
        />
      </el-form-item>

      <el-form-item label="真实姓名" prop="real_name">
        <el-input
          v-model="form.real_name"
          placeholder="请输入真实姓名"
          maxlength="20"
          clearable
        />
      </el-form-item>

      <el-form-item>
        <el-button
          type="primary"
          :loading="loading"
          @click="handleSubmit"
          size="large"
        >
          保存修改
        </el-button>
        <el-button @click="formRef.resetFields()">重置</el-button>
      </el-form-item>
    </el-form>

    <!-- 修改密码 -->
    <el-collapse class="password-collapse">
      <el-collapse-item title="修改密码" name="password">
        <el-form
          ref="passwordFormRef"
          :model="passwordForm"
          :rules="passwordRules"
          label-width="90px"
          label-position="left"
          class="password-form"
        >
          <el-form-item label="旧密码" prop="old_password">
            <el-input
              v-model="passwordForm.old_password"
              type="password"
              placeholder="请输入旧密码"
              show-password
              maxlength="30"
            />
          </el-form-item>

          <el-form-item label="新密码" prop="new_password">
            <el-input
              v-model="passwordForm.new_password"
              type="password"
              placeholder="请输入新密码"
              show-password
              maxlength="30"
            />
          </el-form-item>

          <el-form-item label="确认密码" prop="confirm_password">
            <el-input
              v-model="passwordForm.confirm_password"
              type="password"
              placeholder="请再次输入新密码"
              show-password
              maxlength="30"
            />
          </el-form-item>

          <el-form-item>
            <el-button
              type="primary"
              :loading="passwordLoading"
              @click="handlePasswordChange"
            >
              修改密码
            </el-button>
            <el-button @click="passwordFormRef.resetFields()">重置</el-button>
          </el-form-item>
        </el-form>
      </el-collapse-item>
    </el-collapse>
  </div>
</template>

<style scoped>
.profile-page {
  max-width: 600px;
}

.page-header {
  margin-bottom: 24px;
}

.page-header h2 {
  font-size: 20px;
  font-weight: 600;
  color: #303133;
  margin: 0 0 6px;
}

.page-desc {
  font-size: 14px;
  color: #909399;
  margin: 0;
}

.profile-form {
  margin-bottom: 12px;
}

.profile-form :deep(.el-form-item__label) {
  font-weight: 500;
  color: #606266;
}

.password-collapse {
  margin-top: 8px;
  border: none;
}

.password-collapse :deep(.el-collapse-item__header) {
  font-size: 15px;
  font-weight: 600;
  color: #303133;
  padding: 12px 0;
  border: none;
}

.password-collapse :deep(.el-collapse-item__wrap) {
  border: none;
}

.password-collapse :deep(.el-collapse-item__content) {
  padding: 16px 0 8px;
}

.password-form :deep(.el-form-item__label) {
  font-weight: 500;
  color: #606266;
}

@media (max-width: 768px) {
  .profile-form,
  .password-form {
    :deep(.el-form-item) {
      display: block;
    }

    :deep(.el-form-item__label) {
      width: auto !important;
      margin-bottom: 4px;
      text-align: left;
    }
  }
}
</style>
