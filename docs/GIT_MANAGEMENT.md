# FM Radio Player V0.1 - Git管理文档

## 1. 仓库信息

| 项目名称 | FM Radio Player |
|---------|---------------|
| 项目版本 | V0.1 开发版 |
| Git仓库地址 | https://github.com/[username]/fm-radio-player.git |
| 主要分支 | main, develop |
| 提交历史 | 初始提交: 2023-01-01 - 项目初始化<br>v0.1-dev: 2025-12-25 - V0.1开发版发布 |

## 2. 分支管理策略

### 2.1 分支类型

| 分支类型 | 用途 | 来源分支 | 合并目标 | 命名格式 |
|---------|------|---------|---------|---------|
| main | 生产环境部署 | develop | - | main |
| develop | 集成新功能 | main | main | develop |
| feature/* | 开发新功能 | develop | develop | feature/[feature-name] |
| bugfix/* | 修复bug | develop | develop | bugfix/[bug-description] |
| hotfix/* | 紧急修复生产环境问题 | main | main, develop | hotfix/[issue-description] |

### 2.2 分支管理流程

#### 2.2.1 主分支 (main)
- 用于生产环境部署
- 只接受来自develop分支的合并
- 每次合并后打标签，格式：vX.Y.Z
- 保持稳定，不允许直接提交代码

#### 2.2.2 开发分支 (develop)
- 用于集成新功能
- 接受来自feature/*分支的合并
- 定期合并到main分支
- 保持可构建状态

#### 2.2.3 功能分支 (feature/*)
- 用于开发新功能
- 从develop分支创建
- 功能开发完成后合并到develop分支
- 分支命名示例：feature/channel-search

#### 2.2.4 修复分支 (bugfix/*)
- 用于修复bug
- 从develop分支创建
- 修复完成后合并到develop分支
- 分支命名示例：bugfix/player-initialization-error

#### 2.2.5 热修复分支 (hotfix/*)
- 用于紧急修复生产环境问题
- 从main分支创建
- 修复完成后同时合并到main和develop分支
- 分支命名示例：hotfix/audio-stream-error

### 2.3 分支操作示例

#### 创建功能分支
```bash
git checkout develop
git pull origin develop
git checkout -b feature/new-feature
```

#### 合并功能分支
```bash
git checkout develop
git pull origin develop
git merge feature/new-feature --no-ff
git push origin develop
git branch -d feature/new-feature
```

#### 创建并合并热修复分支
```bash
git checkout main
git pull origin main
git checkout -b hotfix/critical-bug
git commit -m "fix: 修复生产环境关键bug"
git checkout main
git merge hotfix/critical-bug --no-ff
git tag v1.0.1
git checkout develop
git merge hotfix/critical-bug --no-ff
git push origin main develop v1.0.1
git branch -d hotfix/critical-bug
```

## 3. 提交规范

### 3.1 提交信息格式
```
[类型]: [描述]

[详细说明]
```

### 3.2 提交类型

| 类型 | 描述 |
|-----|------|
| feat | 新功能 |
| fix | 修复bug |
| docs | 文档更新 |
| style | 代码格式调整 |
| refactor | 代码重构 |
| test | 测试代码 |
| chore | 构建流程或辅助工具更新 |
| perf | 性能优化 |
| ci | CI/CD配置更新 |
| build | 构建系统更新 |
| revert | 回退到之前的提交 |

### 3.3 提交示例

```
feat: 添加频道搜索功能

- 实现了频道搜索API
- 添加了搜索结果页面
- 优化了搜索算法
- 添加了搜索历史记录功能
```

```
fix: 修复播放器初始化失败问题

- 修复了DPlayer初始化时的undefined错误
- 添加了防御性检查
- 优化了错误处理逻辑
```

### 3.4 提交规则

1. **每次提交只包含一个逻辑变更**
2. **提交信息要简洁明了**
3. **使用中文描述，便于团队沟通**
4. **详细说明部分要具体，说明变更的原因和影响**
5. **不要使用模糊的描述，如"修复了一些问题"**
6. **不要包含敏感信息**

## 4. 版本控制

### 4.1 版本号格式
- 主版本号.次版本号.修订号
- 示例：1.0.0

### 4.2 版本号变更规则

| 版本号部分 | 变更条件 | 示例 |
|---------|---------|------|
| 主版本号 | 不兼容的API变更 | 1.0.0 → 2.0.0 |
| 次版本号 | 向下兼容的功能性新增 | 1.0.0 → 1.1.0 |
| 修订号 | 向下兼容的问题修正 | 1.0.0 → 1.0.1 |

### 4.3 版本发布流程

1. **合并代码**：从develop分支合并到main分支
2. **打标签**：
   ```bash
   git tag v1.0.0
   ```
3. **推送标签**：
   ```bash
   git push origin v1.0.0
   ```
4. **更新版本号**：更新项目配置文件中的版本号
5. **发布说明**：编写发布说明，描述版本变更内容

## 5. 协作规范

### 5.1 代码审查

1. **所有代码变更必须经过代码审查**
2. **创建Pull Request时，填写详细的变更说明**
3. **至少需要1名团队成员审核通过**
4. **审核时要关注代码质量、安全性和可维护性**
5. **及时处理审核意见**

### 5.2 冲突解决

1. **定期拉取最新代码**：
   ```bash
   git pull origin [branch-name]
   ```
2. **遇到冲突时，仔细分析冲突原因**
3. **解决冲突后，进行测试**
4. **提交冲突解决方案**

### 5.3 提交频率

1. **频繁提交**：建议每完成一个小功能或修复一个bug就提交一次
2. **保持提交粒度小**：便于回滚和审查
3. **不要积压大量变更再提交**

## 6. 最佳实践

### 6.1 本地开发

1. **定期拉取最新代码**
2. **使用合适的分支进行开发**
3. **编写测试用例**
4. **运行本地测试**
5. **确保代码符合规范**

### 6.2 远程操作

1. **使用HTTPS或SSH协议**
2. **不要直接推送main分支**
3. **定期清理远程分支**
4. **使用.gitignore文件忽略不需要提交的文件**

### 6.3 日志管理

1. **使用清晰的提交信息**
2. **使用git log查看提交历史**
3. **使用git blame查看代码责任人**
4. **使用git bisect定位问题**

## 7. Git配置

### 7.1 全局配置

```bash
# 设置用户名
git config --global user.name "Your Name"

# 设置邮箱
git config --global user.email "your.email@example.com"

# 设置默认编辑器
git config --global core.editor "vim"

# 设置换行符处理
git config --global core.autocrlf input

# 设置颜色
git config --global color.ui true
```

### 7.2 项目特定配置

```bash
# 进入项目目录
cd /www/wwwroot/fm.liy.ink

# 设置项目特定的用户名和邮箱
git config user.name "Your Name"
git config user.email "your.email@example.com"
```

## 8. 常见问题及解决方案

### 8.1 忘记提交某个文件

```bash
git add [file]
git commit --amend --no-edit
```

### 8.2 提交信息错误

```bash
git commit --amend -m "正确的提交信息"
```

### 8.3 回退到之前的提交

```bash
# 查看提交历史
git log

# 回退到指定提交
git reset --hard [commit-hash]

# 强制推送
git push -f origin [branch-name]
```

### 8.4 撤销本地修改

```bash
# 撤销所有本地修改
git checkout -- .

# 撤销特定文件的修改
git checkout -- [file]
```

## 9. 团队协作工具

| 工具 | 用途 |
|-----|------|
| GitHub | 代码托管 |
| GitLab | 代码托管（备选） |
| Bitbucket | 代码托管（备选） |
| Jira | 任务管理 |
| Confluence | 文档管理 |
| Slack | 团队沟通 |

## 10. 代码审查模板

```markdown
# 代码审查

## 基本信息
- 分支：feature/[feature-name]
- 作者：[author]
- 审查人：[reviewer]
- 截止日期：[date]

## 变更概述
- 新增功能：
- 修复的问题：
- 影响范围：

## 审查要点
- [ ] 代码质量
- [ ] 安全性
- [ ] 可维护性
- [ ] 性能
- [ ] 测试覆盖

## 审查意见
1. 
2. 
3. 

## 审查结果
- [ ] 接受
- [ ] 拒绝
- [ ] 需要修改
```

## 11. 版本发布模板

```markdown
# FM Radio Player vX.Y.Z 发布说明

## 发布日期
[YYYY-MM-DD]

## 版本类型
- [ ] 主版本 (Breaking Change)
- [ ] 次版本 (New Feature)
- [ ] 修订版本 (Bug Fix)

## 变更内容

### 新增功能
- 

### 修复的问题
- 

### 性能优化
- 

### 其他变更
- 

## 升级指南

## 兼容性
- 

## 已知问题
- 
```

## 12. 参考资料

- [Git官方文档](https://git-scm.com/doc)
- [Pro Git](https://git-scm.com/book/zh/v2)
- [GitHub Flow](https://guides.github.com/introduction/flow/)
- [Git Flow](https://nvie.com/posts/a-successful-git-branching-model/)

## 13. 附录

### 13.1 Git常用命令

| 命令 | 用途 |
|-----|------|
| git init | 初始化Git仓库 |
| git clone | 克隆远程仓库 |
| git status | 查看工作区状态 |
| git add | 添加文件到暂存区 |
| git commit | 提交代码 |
| git push | 推送代码到远程仓库 |
| git pull | 拉取远程仓库代码 |
| git branch | 查看分支 |
| git checkout | 切换分支 |
| git merge | 合并分支 |
| git log | 查看提交历史 |
| git diff | 查看差异 |
| git reset | 重置提交 |
| git tag | 打标签 |

### 13.2 .gitignore模板

```gitignore
# Laravel
/node_modules
/public/hot
/storage/framework/*
/storage/logs/*
.env
.env.backup
*.log
npm-debug.log*
yarn-debug.log*
yarn-error.log*

# IDE
.idea
.vscode
*.swp
*.swo
*~
.DS_Store

# Audio Service
/audio-service/venv
/audio-service/*.pyc
/audio-service/__pycache__
/audio-service/audio-service.log
/audio-service/hls_output
```
