<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../../admin/template.xsl" />

  <xsl:template match="content" mode="content">
    <h2>
      <xsl:text>Права на документы типа «</xsl:text>
      <a href="admin/node/{@id}">
        <xsl:value-of select="@title" />
      </a>
      <xsl:text>»</xsl:text>
    </h2>
    <form method="post" action="admin/structure/access?type={@name}&amp;destination={@next}">
      <table class="classic" id="access">
        <thead>
          <tr>
            <th>Группа</th>
            <th class="c" title="Создание (create)">C</th>
            <th class="c" title="Чтение (read)">R</th>
            <th class="c" title="Изменение (update)">U</th>
            <th class="c" title="Удаление (delete)">D</th>
            <th class="c" title="Публикация (publish)">P</th>
          </tr>
        </thead>
        <tbody>
          <xsl:apply-templates select="perm" />
        </tbody>
      </table>
      <input type="submit" value="Сохранить" />
      <input type="reset" value="Вернуть как было" />
    </form>
  </xsl:template>

  <xsl:template match="perm">
    <tr>
      <th>
        <xsl:value-of select="@name" />
      </th>
      <td>
        <input type="checkbox" name="{@gid}[c]">
          <xsl:if test="@create">
            <xsl:attribute name="checked">checked</xsl:attribute>
          </xsl:if>
        </input>
      </td>
      <td>
        <input type="checkbox" name="{@gid}[r]">
          <xsl:if test="@read">
            <xsl:attribute name="checked">checked</xsl:attribute>
          </xsl:if>
        </input>
      </td>
      <td>
        <input type="checkbox" name="{@gid}[u]">
          <xsl:if test="@update">
            <xsl:attribute name="checked">checked</xsl:attribute>
          </xsl:if>
        </input>
      </td>
      <td>
        <input type="checkbox" name="{@gid}[d]">
          <xsl:if test="@delete">
            <xsl:attribute name="checked">checked</xsl:attribute>
          </xsl:if>
        </input>
      </td>
      <td>
        <input type="checkbox" name="{@gid}[p]">
          <xsl:if test="@publish">
            <xsl:attribute name="checked">checked</xsl:attribute>
          </xsl:if>
        </input>
      </td>
    </tr>
  </xsl:template>
</xsl:stylesheet>
