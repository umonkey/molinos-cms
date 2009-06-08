<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../../admin/template.xsl" />

  <xsl:template match="content[@name='addfile']" mode="content">
    <h2>
      <xsl:value-of select="@title" disable-output-escaping="yes" />
    </h2>
    <form id="addfiles" method="post" action="{@target}" enctype="multipart/form-data">
      <ol class="{@mode}">
        <li id="cloneme">
          <xsl:apply-templates select="." mode="addfilectl" />
        </li>
        <li>
          <xsl:apply-templates select="." mode="addfilectl" />
        </li>
        <li>
          <xsl:apply-templates select="." mode="addfilectl" />
        </li>
        <li>
          <xsl:apply-templates select="." mode="addfilectl" />
        </li>
        <li>
          <xsl:apply-templates select="." mode="addfilectl" />
        </li>
      </ol>
      <xsl:if test="@mode='remote'">
        <p><label><input type="checkbox" name="symlink" value="1" /> не скачивать файлы, использовать ссылки</label></p>
      </xsl:if>
      <p>
        <input type="submit" value="Продолжить" />
        <span class="jsonly">
          <xsl:text> или </xsl:text>
          <span class="add fakelink">добавить ещё файлов</span>
        </span>
      </p>
    </form>
    <xsl:apply-templates select="." mode="help" />
  </xsl:template>

  <xsl:template match="content[@name='addfile' and @mode='ftp']" mode="content">
    <h2>
      <xsl:value-of select="@title" disable-output-escaping="yes" />
    </h2>
    <form id="addfiles" class="ftp" method="post" action="{@target}" enctype="multipart/form-data">
      <table class="classic">
        <thead>
          <tr>
            <th />
            <th />
            <th>Имя файла</th>
            <th class="r">Размер</th>
            <th>Загружен</th>
          </tr>
        </thead>
        <tbody>
          <xsl:for-each select="file">
            <tr>
              <td class="r">
                <xsl:value-of select="position()" />
                <xsl:text>.</xsl:text>
              </td>
              <td>
                <input type="checkbox" name="files[]" value="{@name}" />
              </td>
              <td>
                <xsl:value-of select="@name" />
              </td>
              <td class="r">
                <xsl:value-of select="@sizeh" />
              </td>
              <td>
                <xsl:value-of select="@time" />
              </td>
            </tr>
          </xsl:for-each>
        </tbody>
      </table>
      <p>
        <label><input type="checkbox" name="all" value="1" /> Выбрать все</label><br/>
        <label><input type="checkbox" name="preserve" value="1" /> Не удалять файлы после добавления</label>
      </p>
      <input type="submit" value="Продолжить" />
    </form>
    <xsl:apply-templates select="." mode="help" />
  </xsl:template>

  <xsl:template match="content" mode="addfilectl">
    <input type="file" name="files[]" />
  </xsl:template>

  <xsl:template match="content[@mode='remote']" mode="addfilectl">
    <input type="text" name="files[]" />
  </xsl:template>

  <xsl:template match="content[@name='addfile']" mode="help">
    <div class="help">
      <xsl:if test="help">
        <xsl:value-of select="help" disable-output-escaping="yes" />
      </xsl:if>
      <xsl:if test="mode">
        <p>Есть и другие способы загрузить файлы:</p>
        <ul>
          <xsl:for-each select="mode">
            <li>
              <a href="{@href}">
                <xsl:value-of select="text()" />
              </a>
            </li>
          </xsl:for-each>
        </ul>
      </xsl:if>
    </div>
  </xsl:template>
</xsl:stylesheet>
